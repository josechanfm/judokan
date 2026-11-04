<?php

namespace App\Imports;

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Config;
use App\Services\CompetitionCategoryNormalizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * 單一賽事的賽果匯入。
 *
 * 格式同 public/templates/competition_result_list.xlsx：
 *  第 1 列中文欄名、第 2 列欄位代號（name / score / category / gender / weight）、第 3 列起資料。
 *
 * - 只需要填「姓名」同「分數」，年齡組別／性別／重量級別是用來新增報名同分辨同名選手。
 * - 分數會自動對應積分表的名次；對不到名次時只更新分數，名次維持不變。
 * - 本場比賽找不到這位選手的報名時，會自動新增一筆報名（俗稱補登成績）。
 */
class CompetitionResultImport implements ToCollection, WithStartRow, WithHeadingRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public const HEADING_ROW = 2;

    public const START_ROW = 3;

    private Competition $competition;

    /** @var array 組別與量級設定 */
    private array $categories;

    /** @var array 分數 => 名次代號（同分取較前的名次） */
    private array $scoreToRank = [];

    /** @var CompetitionApplication[] 本場比賽的報名（含本次新增的） */
    private array $applications = [];

    private int $importedCount = 0;

    private int $rankedCount = 0;

    private int $createdCount = 0;

    public function __construct(Competition $competition)
    {
        $this->competition = $competition;
        $this->categories = $this->decodeJson($competition->categories_weights) ?: [];

        $scores = $competition->score?->toArray();
        if (! $scores) {
            $fallback = $this->decodeJson($competition->result_scores);
            $scores = is_array($fallback) ? $fallback : [];
        }

        foreach ((array) (Config::item('competition_results') ?: []) as $option) {
            $option = (array) $option;
            $value = (string) ($option['value'] ?? '');

            if ($value === '' || ! isset($scores[$value]) || ! is_numeric($scores[$value])) {
                continue;
            }

            $score = (int) round((float) $scores[$value]);

            if (! isset($this->scoreToRank[$score])) {
                $this->scoreToRank[$score] = $value;
            }
        }

        $this->applications = $competition->applications()->get()->all();
    }

    public function headingRow(): int
    {
        return self::HEADING_ROW;
    }

    public function startRow(): int
    {
        return self::START_ROW;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /** 成功對應到名次的筆數 */
    public function rankedCount(): int
    {
        return $this->rankedCount;
    }

    /** 本次自動新增的報名筆數 */
    public function createdCount(): int
    {
        return $this->createdCount;
    }

    public function collection(Collection $rows): void
    {
        $rowNumber = self::START_ROW - 1;

        foreach ($rows as $row) {
            $rowNumber++;

            $cells = collect($row)->map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            });

            if ($cells->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $name = $this->normalizeName($cells['name'] ?? $cells['name_zh'] ?? '');
            $scoreInput = $cells['score'] ?? null;
            $categoryText = trim((string) ($cells['category'] ?? ''));
            $genderText = trim((string) ($cells['gender'] ?? ''));
            $weightText = trim((string) ($cells['weight'] ?? ''));

            if ($name === '') {
                $this->addFailure($rowNumber, $row, 'name', '姓名不能空白');

                continue;
            }

            if ($scoreInput === null || $scoreInput === '') {
                // 匯出檔會包含沒有成績的報名，留空即代表這次不匯入這一行
                continue;
            }

            if (! is_numeric($scoreInput) || (float) $scoreInput < 0) {
                $this->addFailure($rowNumber, $row, 'score', '分數必須是 0 或以上的數字');

                continue;
            }

            $score = (int) round((float) $scoreInput);
            $categoryCode = $categoryText === '' ? null : $this->resolveCategoryCode($categoryText);
            $genderCode = $genderText === '' ? null : $this->resolveGender($genderText);

            $byName = array_values(array_filter(
                $this->applications,
                fn (CompetitionApplication $application) => $this->nameMatches($application, $name)
            ));

            if ($byName === []) {
                $application = $this->createApplication(
                    $rowNumber,
                    $row,
                    $name,
                    $categoryText,
                    $categoryCode,
                    $genderText,
                    $genderCode,
                    $weightText
                );

                if ($application === null) {
                    continue;
                }
            } else {
                $matched = array_values(array_filter(
                    $byName,
                    fn (CompetitionApplication $application) => $this->attributeMatches(
                        $application,
                        $categoryText,
                        $categoryCode,
                        $genderCode,
                        $weightText
                    )
                ));

                if ($matched === []) {
                    $this->addFailure(
                        $rowNumber,
                        $row,
                        'name',
                        "「{$name}」的報名資料與填寫的年齡組別／性別／重量級別不符"
                    );

                    continue;
                }

                if (count($matched) > 1) {
                    $this->addFailure(
                        $rowNumber,
                        $row,
                        'name',
                        "「{$name}」在本場比賽有 ".count($matched).' 筆報名，請填寫年齡組別／性別／重量級別分辨'
                    );

                    continue;
                }

                $application = $matched[0];
            }

            $application->result_score = $score;

            $rank = $this->scoreToRank[$score] ?? null;

            // 分數對不到積分表的名次時只更新分數，不覆蓋原本的名次
            if ($rank !== null) {
                $application->result_rank = $rank;
                $this->rankedCount++;
            }

            $application->save();
            $this->importedCount++;
        }

        // 賽事本身沒有組別設定時，用剛新增的報名補回組別／量級設定
        if ($this->createdCount > 0 && $this->categories === []) {
            (new CompetitionCategoryNormalizer())->normalize($this->competition);
        }
    }

    /**
     * 新增一筆報名。驗證不通過時回傳 null（已記錄錯誤）。
     */
    private function createApplication(
        int $rowNumber,
        $row,
        string $name,
        string $categoryText,
        ?string $categoryCode,
        string $genderText,
        ?string $genderCode,
        string $weightText
    ): ?CompetitionApplication {
        $hasSettings = $this->categories !== [];

        if ($categoryText === '') {
            $this->addFailure($rowNumber, $row, 'category', '本場比賽沒有這位選手的報名，請填寫年齡組別以新增報名');

            return null;
        }

        if ($hasSettings && $categoryCode === null) {
            $this->addFailure($rowNumber, $row, 'category', "組別「{$categoryText}」不在這場賽事的設定中");

            return null;
        }

        if ($genderText === '') {
            $this->addFailure($rowNumber, $row, 'gender', '本場比賽沒有這位選手的報名，請填寫性別以新增報名');

            return null;
        }

        if ($genderCode === null) {
            $this->addFailure($rowNumber, $row, 'gender', "性別「{$genderText}」無法辨識，請填 男／女／混合（或 M／F／X）");

            return null;
        }

        if ($weightText === '') {
            $this->addFailure($rowNumber, $row, 'weight', '本場比賽沒有這位選手的報名，請填寫重量級別以新增報名');

            return null;
        }

        $weightCode = $hasSettings
            ? $this->resolveWeightCode($categoryCode, $genderCode, $weightText)
            : $weightText;

        if ($hasSettings && $weightCode === null) {
            $this->addFailure($rowNumber, $row, 'weight', "量級「{$weightText}」不在這場賽事的設定中");

            return null;
        }

        $application = new CompetitionApplication([
            'competition_id' => $this->competition->id,
            'organization_id' => $this->competition->organization_id,
        ]);

        $isChinese = (bool) preg_match('/\p{Han}/u', $name);

        $application->role = 'athlete';
        $application->name_zh = $isChinese ? $name : null;
        $application->name_fn = $isChinese ? null : $name;
        $application->display_name = $name;
        $application->category = $hasSettings ? $categoryCode : $categoryText;
        $application->gender = $genderCode;
        $application->weight = $hasSettings ? $weightCode : $weightText;
        $application->accepted = true;

        $this->createdCount++;
        $this->applications[] = $application;

        return $application;
    }

    private function nameMatches(CompetitionApplication $application, string $name): bool
    {
        foreach ([$application->name_zh, $application->name_fn, $application->display_name] as $value) {
            if ($value !== null && $this->normalizeName($value) === $name) {
                return true;
            }
        }

        return false;
    }

    private function attributeMatches(
        CompetitionApplication $application,
        string $categoryText,
        ?string $categoryCode,
        ?string $genderCode,
        string $weightText
    ): bool {
        if ($categoryText !== '') {
            $matched = ($categoryCode !== null && (string) $application->category === $categoryCode)
                || (string) $application->category === $categoryText
                || $this->categoryNameOf($application->category) === $categoryText;

            if (! $matched) {
                return false;
            }
        }

        if ($genderCode !== null && (string) $application->gender !== $genderCode) {
            return false;
        }

        if ($weightText !== '' && ! $this->weightMatches($application, $weightText)) {
            return false;
        }

        return true;
    }

    private function weightMatches(CompetitionApplication $application, string $text): bool
    {
        if ((string) $application->weight === $text) {
            return true;
        }

        $code = $this->resolveWeightCode(
            (string) $application->category,
            (string) $application->gender,
            $text
        );

        return $code !== null && (string) $application->weight === (string) $code;
    }

    private function resolveCategoryCode(string $text): ?string
    {
        foreach ($this->categories as $category) {
            $category = (array) $category;
            $code = (string) ($category['code'] ?? '');

            if ($code === '') {
                continue;
            }

            if ($code === $text || mb_strtoupper($code) === mb_strtoupper($text)) {
                return $code;
            }

            if (trim((string) ($category['name'] ?? '')) === $text) {
                return $code;
            }
        }

        return null;
    }

    private function categoryNameOf($code): string
    {
        foreach ($this->categories as $category) {
            $category = (array) $category;

            if ((string) ($category['code'] ?? '') === (string) $code) {
                return trim((string) ($category['name'] ?? ''));
            }
        }

        return '';
    }

    /**
     * 量級可以填代號（60-）或名稱（-60kg）；只在指定的組別＋性別清單裡找。
     */
    private function resolveWeightCode(?string $categoryCode, ?string $genderCode, string $text): ?string
    {
        $category = null;

        foreach ($this->categories as $item) {
            $item = (array) $item;

            if ($categoryCode === null || (string) ($item['code'] ?? '') === (string) $categoryCode) {
                $category = $item;

                break;
            }
        }

        $lists = match ($genderCode) {
            'F' => ['female', 'mixed', 'male'],
            'X' => ['mixed', 'male', 'female'],
            default => ['male', 'mixed', 'female'],
        };

        $search = function (array $category) use ($lists, $text): ?string {
            foreach ($lists as $gender) {
                foreach ((array) ($category[$gender] ?? []) as $weight) {
                    $weight = (array) $weight;
                    $code = (string) ($weight['code'] ?? '');

                    if ($code === '') {
                        continue;
                    }

                    if ($code === $text || trim((string) ($weight['name'] ?? '')) === $text) {
                        return $code;
                    }
                }
            }

            return null;
        };

        if ($category) {
            $code = $search($category);

            if ($code !== null) {
                return $code;
            }
        }

        // 找不到指定組別的清單時，再找其他組別
        foreach ($this->categories as $item) {
            $code = $search((array) $item);

            if ($code !== null) {
                return $code;
            }
        }

        return null;
    }

    private function resolveGender(string $text): ?string
    {
        $map = [
            '男' => 'M', '男子' => 'M', '男性' => 'M', 'm' => 'M', 'male' => 'M', 'men' => 'M',
            '女' => 'F', '女子' => 'F', '女性' => 'F', 'f' => 'F', 'female' => 'F', 'women' => 'F',
            '混合' => 'X', '男女混合' => 'X', 'x' => 'X', 'mixed' => 'X',
        ];

        return $map[mb_strtolower(trim($text))] ?? null;
    }

    private function normalizeName($value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)));
    }

    private function addFailure(int $rowNumber, $row, string $attribute, string $message): void
    {
        $this->onFailure(new Failure($rowNumber, $attribute, [$message], (array) $row));
    }

    private function decodeJson($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }

            return $decoded;
        }

        return $value;
    }
}
