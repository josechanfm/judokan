<?php

namespace App\Imports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\CompetitionScore;
use App\Models\Config;
use App\Models\Member;
use App\Models\Organization;
use App\Services\CompetitionCategoryNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ResultImport implements ToCollection, WithStartRow, WithHeadingRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private Organization $organization;

    private int $importedCount = 0;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function startRow(): int
    {
        return 3;
    }

    public function headingRow(): int
    {
        return 2;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * 逐列匯入比賽成績。
     *
     * 每列 = 一位選手在一場比賽的成績：
     * - 比賽用「比賽名稱＋比賽日期」比對，找不到就用「積分表」建立新比賽
     * - 選手用證件號碼／中文名／外文名比對報名資料，找不到就新增一筆
     * - 積分：該列有填就用填的，否則依比賽的積分表換算
     */
    public function collection(Collection $rows): void
    {
        $resultOptions = collect(Config::item('competition_results') ?: []);
        $rowNumber = $this->startRow() - 1;
        $touched = [];

        foreach ($rows as $row) {
            $rowNumber++;

            if (collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            $fail = function (string $attribute, string $message) use ($rowNumber, $row) {
                $this->onFailure(new Failure($rowNumber, $attribute, [$message], $row->toArray()));
            };

            $title = trim((string) ($row['competition'] ?? ''));
            $date = $this->normalizeDate($row['competition_date'] ?? null);
            $schemeTitle = trim((string) ($row['score_scheme'] ?? ''));
            $nameZh = trim((string) ($row['name_zh'] ?? ''));
            $nameFn = trim((string) ($row['name_fn'] ?? ''));
            $idNum = trim((string) ($row['id_num'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));
            $weight = trim((string) ($row['weight'] ?? ''));
            $roleName = trim((string) ($row['role'] ?? ''));
            $resultText = trim((string) ($row['result'] ?? ''));
            $scoreInput = $row['score'] ?? null;

            if ($title === '') {
                $fail('competition', '比賽名稱不可為空');

                continue;
            }

            if (! $date) {
                $fail('competition_date', '比賽日期格式不正確（請用 YYYY-MM-DD）');

                continue;
            }

            if ($nameZh === '' && $nameFn === '' && $idNum === '') {
                $fail('name_zh', '中文名、外文名、證件號碼至少要填一個');

                continue;
            }

            if ($resultText === '') {
                $fail('result', '成績不可為空');

                continue;
            }

            $competition = Competition::where('organization_id', $this->organization->id)
                ->where('title_zh', $title)
                ->whereDate('start_date', $date)
                ->first();

            if (! $competition) {
                $scoreScheme = $schemeTitle !== ''
                    ? CompetitionScore::where('title', $schemeTitle)->first()
                    : null;

                if (! $scoreScheme) {
                    $fail(
                        'score_scheme',
                        $schemeTitle === ''
                            ? '新的比賽必須填「積分表」（例如：全澳柔道計分賽-二級賽事）'
                            : "找不到積分表「{$schemeTitle}」"
                    );

                    continue;
                }

                $competition = Competition::create([
                    'organization_id' => $this->organization->id,
                    'competition_score_id' => $scoreScheme->id,
                    'title_zh' => $title,
                    'start_date' => $date,
                    'end_date' => $date,
                    // competitions.roles / categories_weights 在資料庫是雙重編碼 JSON，這裡比照既有資料
                    'categories_weights' => '[]',
                    'roles' => json_encode([
                        ['value' => 'athlete', 'label' => '運動員'],
                        ['value' => 'referee', 'label' => '裁判'],
                        ['value' => 'coach', 'label' => '教練'],
                        ['value' => 'staff', 'label' => '工作人員'],
                    ]),
                    'result_scores' => $scoreScheme->toArray(),
                    'published' => false,
                ]);
            }

            $scores = $competition->score?->toArray() ?? ($competition->result_scores ?? []);
            $rank = $this->normalizeRank($resultText, $resultOptions);
            $score = is_numeric($scoreInput) ? (int) $scoreInput : (int) ($scores[$rank] ?? 0);

            $application = $this->findApplication($competition, $idNum, $nameZh, $nameFn);

            if (! $application) {
                $application = new CompetitionApplication([
                    'competition_id' => $competition->id,
                    'organization_id' => $this->organization->id,
                ]);
            }

            $application->name_zh = $application->name_zh ?: ($nameZh ?: null);
            $application->name_fn = $application->name_fn ?: ($nameFn ?: null);
            $application->display_name = $application->display_name
                ?: ($nameZh ?: ($nameFn ?: null));
            $application->id_num = $application->id_num ?: ($idNum ?: null);
            $application->category = $application->category ?: ($category ?: null);
            $application->weight = $application->weight ?: ($weight ?: null);
            $application->role = $application->role ?: $this->normalizeRole($roleName);

            if (! $application->member_id) {
                $member = $this->findMember($nameZh, $nameFn);
                if ($member) {
                    $application->member_id = $member->id;
                }
            }

            $application->result_rank = $rank;
            $application->result_score = $score;
            $application->save();

            $touched[$competition->id] = true;

            $this->importedCount++;
        }

        // 匯入的組別／量級可能是「男子公開組 / -60kg」這種文字，
        // 統一轉成賽事設定的 code，賽果頁才選得到這批運動員
        if ($touched) {
            $normalizer = new CompetitionCategoryNormalizer();
            foreach (Competition::whereIn('id', array_keys($touched))->get() as $item) {
                $normalizer->normalize($item);
            }
        }
    }

    private function findApplication(Competition $competition, string $idNum, string $nameZh, string $nameFn): ?CompetitionApplication
    {
        $query = CompetitionApplication::where('competition_id', $competition->id);

        if ($idNum !== '') {
            return $query->where('id_num', $idNum)->first();
        }

        if ($nameZh !== '') {
            return $query->where('name_zh', $nameZh)->first();
        }

        return $nameFn !== '' ? $query->where('name_fn', $nameFn)->first() : null;
    }

    private function findMember(string $nameZh, string $nameFn): ?Member
    {
        $query = Member::whereHas('organizations', function ($q) {
            $q->where('organizations.id', $this->organization->id);
        });

        if ($nameZh !== '' && $nameFn !== '') {
            return (clone $query)->where('name_zh', $nameZh)->where('name_fn', $nameFn)->first()
                ?? (clone $query)->where('name_zh', $nameZh)->first()
                ?? (clone $query)->where('name_fn', $nameFn)->first();
        }

        if ($nameZh !== '') {
            return (clone $query)->where('name_zh', $nameZh)->first();
        }

        return $nameFn !== '' ? (clone $query)->where('name_fn', $nameFn)->first() : null;
    }

    /**
     * 成績文字 → 名次代號（first/second/third/fifth/seventh/advance/participate）。
     * 認不出來就保留原文，分數改由「積分」欄位決定。
     */
    private function normalizeRank(string $text, Collection $options): string
    {
        $map = [
            '冠軍' => 'first', '第一名' => 'first', '第一' => 'first', '1' => 'first', '1st' => 'first', 'first' => 'first',
            '亞軍' => 'second', '第二名' => 'second', '第二' => 'second', '2' => 'second', '2nd' => 'second', 'second' => 'second',
            '季軍' => 'third', '第三名' => 'third', '第三' => 'third', '3' => 'third', '3rd' => 'third', 'third' => 'third',
            '第五名' => 'fifth', '第五' => 'fifth', '5' => 'fifth', '5th' => 'fifth', 'fifth' => 'fifth',
            '第七名' => 'seventh', '第七' => 'seventh', '7' => 'seventh', '7th' => 'seventh', 'seventh' => 'seventh',
            '晉級' => 'advance', 'advance' => 'advance',
            '出席' => 'participate', '參加' => 'participate', 'participate' => 'participate',
        ];

        $key = mb_strtolower(trim($text));
        if (isset($map[$key])) {
            return $map[$key];
        }

        // 也可以直接填代號（first/second/...）或選項的 label
        foreach ($options as $option) {
            if (($option->value ?? null) === $text
                || ($option->label ?? null) === $text
                || ($option->label_zh ?? null) === $text
                || ($option->label_en ?? null) === $text) {
                return $option->value;
            }
        }

        return $text;
    }

    private function normalizeRole(string $text): string
    {
        $map = [
            '運動員' => 'athlete', '選手' => 'athlete', 'athlete' => 'athlete',
            '教練' => 'coach', 'coach' => 'coach',
            '裁判' => 'referee', 'referee' => 'referee',
            '領隊' => 'staff', '工作人員' => 'staff', 'staff' => 'staff',
        ];

        $key = mb_strtolower(trim($text));

        return $map[$key] ?? 'athlete';
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            return Carbon::parse(trim((string) $value))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
