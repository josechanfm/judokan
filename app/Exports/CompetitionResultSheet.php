<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * 賽果匯出：資料工作表。
 */
class CompetitionResultSheet implements FromArray, WithTitle, WithColumnWidths
{
    private Competition $competition;

    private array $rows;

    public function __construct(Competition $competition, array $rows)
    {
        $this->competition = $competition;
        $this->rows = $rows;
    }

    public function title(): string
    {
        return '賽果';
    }

    public function array(): array
    {
        $data = [
            // 第 1 列：中文標題
            [
                '比賽名稱',
                '比賽日期',
                '積分表',
                '中文名',
                '外文名',
                '證件號碼',
                '組別',
                '量級',
                '職位',
                '成績',
                '積分',
            ],
            // 第 2 列：欄位代號（匯入時用來對應欄位，請勿修改）
            [
                'competition',
                'competition_date',
                'score_scheme',
                'name_zh',
                'name_fn',
                'id_num',
                'category',
                'weight',
                'role',
                'result',
                'score',
            ],
        ];

        foreach ($this->rows as $row) {
            $data[] = $row;
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 14,
            'C' => 26,
            'D' => 14,
            'E' => 20,
            'F' => 16,
            'G' => 14,
            'H' => 12,
            'I' => 12,
            'J' => 12,
            'K' => 10,
        ];
    }
}
