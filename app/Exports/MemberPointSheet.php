<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * 積分統計匯出的單一工作表。
 *
 * 格式固定為：第 1 列中文欄名、第 2 列欄位代號、第 3 列起資料，
 * 與匯入用的解析方式（WithHeadingRow + WithStartRow）一致，所以可以直接再匯入。
 *
 * 必須實作 WithStrictNullComparison，否則 0 會被當成 null 跳過唔寫，
 * 匯出後再匯入就會變成空白（例如 0 分）。
 */
class MemberPointSheet implements FromArray, WithColumnWidths, WithStrictNullComparison, WithTitle
{
    private string $sheetTitle;

    private array $headings;

    private array $columns;

    private array $rows;

    private array $widths;

    public function __construct(
        string $sheetTitle,
        array $headings,
        array $columns,
        array $rows,
        array $widths = []
    ) {
        $this->sheetTitle = $sheetTitle;
        $this->headings = $headings;
        $this->columns = $columns;
        $this->rows = $rows;
        $this->widths = $widths;
    }

    public function array(): array
    {
        return array_merge([$this->headings, $this->columns], $this->rows);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function columnWidths(): array
    {
        return $this->widths;
    }
}
