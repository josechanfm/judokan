<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * 賽果匯出：說明工作表（填寫方式、可用的成績與積分）。
 */
class CompetitionResultGuideSheet implements FromArray, WithTitle, WithColumnWidths
{
    private Competition $competition;

    private array $rankOptions;

    private array $scores;

    public function __construct(Competition $competition, array $rankOptions = [], array $scores = [])
    {
        $this->competition = $competition;
        $this->rankOptions = $rankOptions;
        $this->scores = $scores;
    }

    public function title(): string
    {
        return '說明';
    }

    public function array(): array
    {
        $rows = [
            ['賽果匯入說明'],
            ['賽事', $this->competition->title_zh ?: $this->competition->title_fn],
            ['比賽日期', $this->competition->start_date],
            ['積分表', $this->competition->score->title ?? ''],
            [],
            ['1. 請勿修改「賽果」工作表的第 1、2 列（欄位名稱與代號）。'],
            ['2. 第 3 列開始，每一列代表一位選手在這場比賽的成績。'],
            ['3. 「成績」可填代號或中文（例如 first 或 第一名）。'],
            ['4. 「積分」留空會依本賽事的積分表自動計算。'],
            ['5. 選手以「證件號碼」比對報名資料，沒有證件號碼時會用中文名／外文名。'],
            ['6. 「組別」、「量級」可填代號或名稱，用來區分同一人報名多個量級的情況。'],
            [],
            ['成績代號', '名稱', '本賽事積分'],
        ];

        foreach ($this->rankOptions as $option) {
            $value = $option['value'] ?? null;
            if (! $value) {
                continue;
            }
            $rows[] = [$value, $option['label'] ?? $value, $this->scores[$value] ?? ''];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 22,
            'C' => 16,
        ];
    }
}
