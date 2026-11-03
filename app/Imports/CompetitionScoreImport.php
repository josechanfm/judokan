<?php

namespace App\Imports;

use App\Models\CompetitionScore;
use App\Models\Config;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Validators\Failure;

/**
 * 導入積分表（積分類別 + 各名次分數）。
 *
 * 對應的範本：public/templates/competition_score_list.xlsx
 *  第 1 列 = 中文欄名（給人看）
 *  第 2 列 = 欄位代號（程式用，WithHeadingRow 讀這一列）
 *  第 3 列起 = 資料
 */
class CompetitionScoreImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public const HEADING_ROW = 2;
    public const START_ROW = 3;

    /** 各名次的分數欄位 */
    public const SCORE_FIELDS = [
        'first',
        'second',
        'third',
        'fifth',
        'seventh',
        'advance',
        'participate',
    ];

    private int $imported = 0;

    /** 本次檔案已用過的「組別 + 名稱」，避免同一個檔案裡重覆匯入 */
    private array $seen = [];

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
        return $this->imported;
    }

    public function collection(Collection $rows): void
    {
        $categories = collect(Config::item('competition_score_categories') ?: []);
        $rowNumber = self::START_ROW;

        foreach ($rows as $row) {
            $cells = $row->toArray();

            // 整列空白就跳過（仍然要讓列號前進，錯誤訊息才會對到 Excel 的列）
            if ($this->isEmptyRow($cells)) {
                $rowNumber++;

                continue;
            }

            $title = trim((string) $this->value($cells, 'title'));
            $category = $this->resolveCategory($this->value($cells, 'category'), $categories);

            $errors = [];

            if ($title === '') {
                $errors['title'][] = '名稱不能空白';
            }

            if ($category === null) {
                $errors['category'][] = '組別必須是設定中的組別代號或名稱（例如 LOCAL 或 本地）';
            }

            $scores = [];

            foreach (self::SCORE_FIELDS as $field) {
                $raw = $this->value($cells, $field);

                // 空白視為 0 分（例如這個組別沒有第七名）
                if ($raw === null || $raw === '') {
                    $scores[$field] = 0;

                    continue;
                }

                if (! is_numeric($raw) || (float) $raw < 0) {
                    $errors[$field][] = '必須是 0 或以上的數字';

                    continue;
                }

                $scores[$field] = (int) round((float) $raw);
            }

            if ($category !== null && $title !== '') {
                $key = $category.'|'.$title;

                if (isset($this->seen[$key])) {
                    $errors['title'][] = '同一檔案內已有相同組別與名稱的積分表';
                } elseif ($this->exists($category, $title)) {
                    $errors['title'][] = '此組別與名稱的積分表已存在';
                }
            }

            if ($errors !== []) {
                foreach ($errors as $attribute => $messages) {
                    $this->onFailure(new Failure($rowNumber, $attribute, $messages));
                }

                $rowNumber++;

                continue;
            }

            CompetitionScore::create([
                'organization_id' => 0,
                'category' => $category,
                'title' => $title,
                ...$scores,
            ]);

            $this->seen[$category.'|'.$title] = true;
            $this->imported++;
            $rowNumber++;
        }
    }

    /**
     * 組別可填代號（LOCAL）或名稱（本地），統一存代號。
     */
    private function resolveCategory(mixed $raw, Collection $categories): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $byValue = $categories->first(
            fn ($category) => mb_strtoupper((string) ($category->value ?? '')) === mb_strtoupper($raw)
        );

        if ($byValue) {
            return (string) $byValue->value;
        }

        $byLabel = $categories->first(
            fn ($category) => trim((string) ($category->label ?? '')) === $raw
        );

        return $byLabel ? (string) $byLabel->value : null;
    }

    private function exists(string $category, string $title): bool
    {
        return CompetitionScore::where('organization_id', 0)
            ->where('category', $category)
            ->where('title', $title)
            ->exists();
    }

    private function value(array $cells, string $key): mixed
    {
        return $cells[$key] ?? null;
    }

    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
