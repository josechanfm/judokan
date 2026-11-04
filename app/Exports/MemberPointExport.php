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

        $scopeLabels = [
            'all' => '本館運動員 + 集訓隊成員',
            'club' => '本館運動員',
            'team' => '集訓隊成員',
        ];
        $scope = $this->filters['athlete_scope'] ?? 'all';
        $rows[] = ['範圍', $scopeLabels[$scope] ?? $scope];

        if (! empty($this->filters['keyword'])) {
            $rows[] = ['搜尋', $this->filters['keyword']];
        }
        $rows[] = [];

        // 積分排行
        $rows[] = ['排名', '會員', '會員號碼', '比賽場次', '比賽積分', '集訓積分', '總分'];
        foreach ($this->ranking as $index => $row) {
            $rows[] = [
                $index + 1,
                $row['name'],
                $row['member_number'] ?: ($row['is_member'] ? '' : '非會員'),
                $row['competition_count'],
                $row['competition_score'] ?? $row['total_score'],
                $row['training_score'] ?? 0,
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

        // 集訓明細（每月四項次數）
        $rows[] = [];
        $rows[] = ['集訓明細'];
        $rows[] = ['會員', '月份', '中心/集訓', '得勝', '集訓(節數)', '比賽日數', '積分'];
        foreach ($this->ranking as $row) {
            foreach ($row['training'] ?? [] as $training) {
                $rows[] = [
                    $row['name'],
                    $training['month'] ?? '',
                    $training['centre_count'] ?? 0,
                    $training['win_count'] ?? 0,
                    $training['session_count'] ?? 0,
                    $training['competition_day_count'] ?? 0,
                    $training['points'] ?? 0,
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
    }}
