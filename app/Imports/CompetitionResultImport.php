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
 * 使用與 public/templates/result_list.xlsx 相同的欄位格式：
 * 第 1 列中文標題、第 2 列欄位代號、第 3 列起資料。
 *
 * 成績可以填代號（first）或中文（第一名）；
 * 組別／量級／職位同樣可以用代號或名稱。
 * 找不到報名資料時會自動建立一筆報名（方便補登沒有報名紀錄的成績）。
 */
class CompetitionResultImport implements ToCollection, WithStartRow, WithHeadingRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private Competition $competition;

    /** @var array 組別與量級設定 */
    private array $categories;

    /** @var array 名次積分（first => 70 ...） */
    private array $scores;

    /** @var array 成績代號／名稱 => 代號 */
    private array $rankMap = [];

    /** @var array 職位代號／名稱 => 代號 */
    private array $roleMap = [];

    private int $importedCount = 0;

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
        $this->scores = $scores;

        foreach ((array) (Config::item('competition_results') ?: []) as $option) {
            $option = (array) $option;
            $value = (string) ($option['value'] ?? '');
            $label = (string) ($option['label'] ?? '');
            if ($value === '') {
                continue;
            }
            $this->rankMap[$value] = $value;
            if ($label !== '') {
                $this->rankMap[$label] = $value;
            }
        }

        foreach ($this->decodeJson($competition->roles) ?: [] as $role) {
            $role = (array) $role;
            $value = (string) ($role['value'] ?? '');
            $label = (string) ($role['label'] ?? '');
            if ($value === '') {
                continue;
            }
            $this->roleMap[$value] = $value;
            if ($label !== '') {
                $this->roleMap[$label] = $value;
            }
        }
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

    public function createdCount(): int
    {
        return $this->createdCount;
    }

    public function collection(Collection $rows): void
    {
        $rowNumber = $this->startRow() - 1;

        foreach ($rows as $row) {
            $rowNumber++;

            $values = collect($row)->map(function ($value) {
                return is_string($value) ? trim($value) : $value;
            });

            if ($values->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            $fail = function (string $attribute, string $message) use ($rowNumber, $row) {
                $this->onFailure(new Failure($rowNumber, $attribute, [$message], $row->toArray()));
            };

            $nameZh = (string) ($values['name_zh'] ?? '');
            $nameFn = (string) ($values['name_fn'] ?? '');
            $idNum = (string) ($values['id_num'] ?? '');
            $resultText = (string) ($values['result'] ?? '');
            $scoreInput = $values['score'] ?? null;

            if ($nameZh === '' && $nameFn === '' && $idNum === '') {
                $fail('name_zh', '中文名、外文名、證件號碼至少要填一個');

                continue;
            }

            if ($resultText === '') {
                $fail('result', '成績不可為空');

                continue;
            }

            $rank = $this->resolveRank($resultText);
            if (! $rank) {
                $fail(
                    'result',
                    "成績「{$resultText}」無法辨識，可用：".implode('、', array_keys($this->rankMap))
                );

                continue;
            }

            $categoryCode = $this->resolveCategory($values['category'] ?? null);
            $weightCode = $this->resolveWeight($categoryCode, $values['weight'] ?? null);
            $role = $this->roleMap[(string) ($values['role'] ?? '')] ?? null;

            $application = $this->findApplication($idNum, $nameZh, $nameFn, $categoryCode, $weightCode);

            if ($application === false) {
                $fail(
                    'weight',
                    '此選手在這場比賽有多筆報名，且組別／量級對不上，請確認後再匯入'
                );

                continue;
            }

            if (! $application) {
                $application = new CompetitionApplication([
                    'competition_id' => $this->competition->id,
                    'organization_id' => $this->competition->organization_id,
                ]);
                $application->role = $role ?: 'athlete';
                $application->name_zh = $nameZh ?: null;
                $application->name_fn = $nameFn ?: null;
                $application->display_name = $nameZh ?: ($nameFn ?: null);
                $application->id_num = $idNum ?: null;
                $application->category = $categoryCode;
                $application->weight = $weightCode;
                $application->accepted = true;
                $this->createdCount++;
            } else {
                // 只補上原本空白的欄位，不覆蓋既有報名資料
                $application->name_zh = $application->name_zh ?: ($nameZh ?: null);
                $application->name_fn = $application->name_fn ?: ($nameFn ?: null);
                $application->display_name = $application->display_name
                    ?: ($nameZh ?: ($nameFn ?: null));
                $application->id_num = $application->id_num ?: ($idNum ?: null);
                $application->category = $application->category ?: $categoryCode;
                $application->weight = $application->weight ?: $weightCode;
            }

            $application->result_rank = $rank;
            $application->result_score = is_numeric($scoreInput)
                ? (int) $scoreInput
                : ($this->scores[$rank] ?? null);
            $application->save();

            $this->importedCount++;
        }

        // 匯入後把報名資料的組別／量級／性別對齊賽事設定，
        // 這樣賽果頁重新整理後就能選到這些運動員
        if ($this->importedCount > 0) {
            (new CompetitionCategoryNormalizer())->normalize($this->competition);
        }
    }

    private function resolveRank(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        if (isset($this->rankMap[$text])) {
            return $this->rankMap[$text];
        }

        $lower = mb_strtolower($text);

        return $this->rankMap[$lower] ?? null;
    }

    private function resolveCategory($text): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach ($this->categories as $category) {
            $category = (array) $category;
            if ((string) ($category['code'] ?? '') === $text) {
                return $text;
            }
            if (trim((string) ($category['name'] ?? '')) === $text) {
                return (string) $category['code'];
            }
        }

        return $text;
    }

    private function resolveWeight(?string $categoryCode, $text): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $category = null;
        foreach ($this->categories as $item) {
            $item = (array) $item;
            if ($categoryCode !== null && (string) ($item['code'] ?? '') === $categoryCode) {
                $category = $item;
                break;
            }
        }

        if (! $category) {
            return $text;
        }

        foreach (['male', 'female'] as $genderKey) {
            foreach ((array) ($category[$genderKey] ?? []) as $weight) {
                $weight = (array) $weight;
                if ((string) ($weight['code'] ?? '') === $text) {
                    return $text;
                }
                if (trim((string) ($weight['name'] ?? '')) === $text) {
                    return (string) $weight['code'];
                }
            }
        }

        return $text;
    }

    /**
     * 在本賽事的報名資料中找出對應選手。
     *
     * 回傳：
     * - CompetitionApplication：找到對應報名
     * - null：找不到報名（由上層自動新增）
     * - false：有多筆報名但組別／量級對不上（避免寫到錯的量級）
     */
    private function findApplication(
        string $idNum,
        string $nameZh,
        string $nameFn,
        ?string $category,
        ?string $weight
    ) {
        $query = CompetitionApplication::where('competition_id', $this->competition->id);

        if ($idNum !== '') {
            $candidates = (clone $query)->where('id_num', $idNum)->get();
        } elseif ($nameZh !== '') {
            $candidates = (clone $query)->where('name_zh', $nameZh)->get();
        } elseif ($nameFn !== '') {
            $candidates = (clone $query)->where('name_fn', $nameFn)->get();
        } else {
            return null;
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        // 同一人報名多個量級時，用組別／量級挑出正確那一筆
        $matched = $candidates->first(function ($application) use ($category, $weight) {
            if ($category && (string) $application->category !== $category) {
                return false;
            }
            if ($weight && (string) $application->weight !== $weight) {
                return false;
            }

            return true;
        });

        if ($matched) {
            return $matched;
        }

        // 有指定組別／量級但完全對不上：多筆報名時不猜測，直接回報錯誤
        if ($candidates->count() > 1 && ($category || $weight)) {
            return false;
        }

        return $candidates->first();
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
