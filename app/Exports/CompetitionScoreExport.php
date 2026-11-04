<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

/**
 * 匯出積分表。
 *
 * 刻意使用與匯入範本相同的格式（第 1 列中文欄名、第 2 列欄位代號、第 3 列起資料），
 * 這樣匯出的檔案可以直接再匯入。
 */
class CompetitionScoreExport implements FromArray, WithColumnWidths
{
    private array $scores;

    private array $categoryCodes;

    /**
     * @param  array  $scores  CompetitionScore 陣列
     * @param  array  $categoryCodes  可用的組別代號（放入標題提示用）
     */
    public function __construct(array $scores, array $categoryCodes = [])
    {
        $this->scores = $scores;
        $this->categoryCodes = $categoryCodes;
    }

    public function array(): array
    {
        $codes = implode(' / ', $this->categoryCodes);

        $rows = [
            [
                '組別（'.$codes.'）',
                '積分表名稱',
                '第一名',
                '第二名',
                '第三名',
                '第五名',
                '第七名',
                '晉級',
                '參加',
            ],
            [
                'category',
                'title',
                'first',
                'second',
                'third',
                'fifth',
                'seventh',
                'advance',
                'participate',
            ],
        ];

        foreach ($this->scores as $score) {
            $rows[] = [
                $score->category,
                $score->title,
                (int) $score->first,
                (int) $score->second,
                (int) $score->third,
                (int) $score->fifth,
                (int) $score->seventh,
                (int) $score->advance,
                (int) $score->participate,
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 44, 'B' => 32, 'C' => 10, 'D' => 10, 'E' => 10,
            'F' => 10, 'G' => 10, 'H' => 10, 'I' => 10,
        ];
    }
}
