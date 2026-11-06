<?php

namespace App\Imports;

use App\Models\Organization;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;

/**
 * 積分統計的匯入（對應 MemberPointExport 匯出的檔案）。
 *
 * 工作表 0「比賽積分」→ 用 ResultImport 解析
 * 工作表 1「集訓記錄」→ 用 TrainingRecordImport 解析
 *
 * 只有一個工作表的檔案（例如原本的成績範本 result_list.xlsx、
 * 或「只匯出比賽積分」的檔案）一樣可以匯入，缺少的工作表會自動略過。
 */
class MemberPointImport implements SkipsUnknownSheets, WithMultipleSheets
{
    use Importable;

    private ResultImport $results;

    private TrainingRecordImport $training;

    public function __construct(Organization $organization)
    {
        $this->results = new ResultImport($organization);
        $this->training = new TrainingRecordImport($organization);
    }

    public function sheets(): array
    {
        return [
            0 => $this->results,
            1 => $this->training,
        ];
    }

    public function onUnknownSheet($sheetName): void
    {
        // 檔案沒有「集訓記錄」工作表時直接略過
    }

    public function competitionCount(): int
    {
        return $this->results->importedCount();
    }

    public function trainingCount(): int
    {
        return $this->training->importedCount();
    }

    public function importedCount(): int
    {
        return $this->competitionCount() + $this->trainingCount();
    }

    public function failures(): Collection
    {
        return collect($this->results->failures()->all())
            ->merge($this->training->failures()->all())
            ->values();
    }
}
