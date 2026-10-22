<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class MemberPointExport implements FromArray, WithColumnWidths
{
    private $ranking;

    private array $filters;

    private array $rankLabels;

    private array $roleLabels;

    /**
     * @param  \Illuminate\Support\Collection  $ranking  同 MemberPointController 的排行資料
     * @param  array  $filters  統計期間等條件
     * @param  array  $rankLabels  名次代號 → 名稱（first → 第一名）
     * @param  array  $roleLabels  職位代號 → 名稱（athlete → 運動員）
     */
    public function __construct($ranking, array $filters, array $rankLabels = [], array $roleLabels = [])
    {
        $this->ranking = $ranking;
        $this->filters = $filters;
        $this->rankLabels = $rankLabels;
        $this->roleLabels = $roleLabels;
    }

    public function array(): array
    {
        $rows = [];

        // 標題
        $rows[] = ['會員積分統計'];
        $rows[] = ['統計期間', ($this->filters['start'] ?? '').' ~ '.($this->filters['end'] ?? '')];
        if (! empty($this->filters['keyword'])) {
            $rows[] = ['搜尋', $this->filters['keyword']];
        }
        $rows[] = [];

        // 積分排行
        $rows[] = ['排名', '會員', '會員號碼', '場次', '總分'];
        foreach ($this->ranking as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['name'],
                $row['member_number'] ?: ($row['is_member'] ? '' : '非會員'),
                $row['competition_count'],
                $row['total_score'],
            ];
        }

        // 成績明細
        $rows[] = [];
        $rows[] = ['成績明細'];
        $rows[] = ['會員', '賽事', '日期', '組別', '量級', '職位', '成績', '積分'];
        foreach ($this->ranking as $row) {
            foreach ($row['results'] as $result) {
                $rows[] = [
                    $row['name'],
                    $result['competition_title'],
                    $result['date'],
                    $result['category'],
                    $result['weight'],
                    $this->roleLabels[$result['role']] ?? $result['role'],
                    $this->rankLabels[$result['result_rank']] ?? $result['result_rank'],
                    $result['result_score'],
                ];
            }
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 34, 'C' => 16, 'D' => 14, 'E' => 14,
            'F' => 12, 'G' => 12, 'H' => 10,
        ];
    }
}
