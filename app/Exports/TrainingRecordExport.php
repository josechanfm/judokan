<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TrainingRecordExport implements FromArray, WithColumnWidths, WithStrictNullComparison
{
    private array $rows;

    private string $month;

    /**
     * @param  array  $rows  同 TrainingRecordController::buildRows 的列
     * @param  string  $month  匯出月份（YYYY-MM）
     */
    public function __construct(array $rows, string $month)
    {
        $this->rows = $rows;
        $this->month = $month;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['每月訓練記錄'];
        $rows[] = ['月份', $this->month];
        $rows[] = [];

        $rows[] = ['會員號碼', '運動員', '類別', '中心/集訓', '得勝', '集訓(節數)', '比賽日數', '積分'];

        $total = ['合計', '', '', 0, 0, 0, 0, 0];

        foreach ($this->rows as $row) {
            $rows[] = [
                $row['member_number'],
                $row['name'],
                $row['athlete_type'] ?? '',
                $row['centre_count'],
                $row['win_count'],
                $row['session_count'],
                $row['competition_day_count'],
                $row['points'],
            ];

            $total[3] += $row['centre_count'];
            $total[4] += $row['win_count'];
            $total[5] += $row['session_count'];
            $total[6] += $row['competition_day_count'];
            $total[7] += $row['points'];
        }

        if ($this->rows !== []) {
            $rows[] = $total;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 26, 'C' => 16, 'D' => 12,
            'E' => 10, 'F' => 12, 'G' => 12, 'H' => 10,
        ];
    }
}
