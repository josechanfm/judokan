<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * 單一賽事的賽果匯出（簡化版）。
 *
 * 格式與匯入範本相同：第 1 列中文欄名、第 2 列欄位代號、第 3 列起資料，
 * 所以可以「匯出 → 填分數 → 再匯入」。
 */
class CompetitionResultExport implements FromArray, WithColumnWidths, WithStrictNullComparison
{
    /** 欄位代號（第 2 列，程式匯入時用） */
    public const COLUMNS = ['name', 'score', 'category', 'gender', 'weight'];

    /** 中文欄名（第 1 列） */
    public const HEADINGS = ['姓名', '分數', '年齡組別', '性別', '重量級別'];

    /** @var array 已整理好的資料列 */
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return array_merge([self::HEADINGS, self::COLUMNS], $this->rows);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 10,
            'C' => 22,
            'D' => 10,
            'E' => 22,
        ];
    }
}
