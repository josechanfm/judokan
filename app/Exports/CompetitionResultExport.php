<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 單一賽事的賽果匯出。
 *
 * 第一個工作表「賽果」的欄位格式與 public/templates/result_list.xlsx 相同
 * （第 1 列中文標題、第 2 列欄位代號、第 3 列起資料），
 * 因此匯出 → 修改 → 再匯入可以直接循環使用。
 */
class CompetitionResultExport implements WithMultipleSheets
{
    private Competition $competition;

    /** @var array 已整理好的資料列 */
    private array $rows;

    /** @var array 名次選項（value / label） */
    private array $rankOptions;

    /** @var array 積分表（first => 70 ...） */
    private array $scores;

    public function __construct(
        Competition $competition,
        array $rows,
        array $rankOptions = [],
        array $scores = []
    ) {
        $this->competition = $competition;
        $this->rows = $rows;
        $this->rankOptions = $rankOptions;
        $this->scores = $scores;
    }

    public function sheets(): array
    {
        return [
            new CompetitionResultSheet($this->competition, $this->rows),
            new CompetitionResultGuideSheet($this->competition, $this->rankOptions, $this->scores),
        ];
    }
}
