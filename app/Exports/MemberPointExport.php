<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 積分統計匯出（可直接再匯入）。
 *
 * 兩種模式：
 * - 比賽積分：只有「比賽積分」工作表（欄位同 ResultImport）
 * - 全部：多一個「集訓記錄」工作表（欄位同 TrainingRecordImport）
 *
 * 每個工作表的格式都是「第 1 列中文欄名、第 2 列欄位代號、第 3 列起資料」，
 * 所以匯出後可以直接在原頁面的「匯入成績」再匯入。
 */
class MemberPointExport implements WithMultipleSheets
{
    /** 比賽積分（對應 App\Imports\ResultImport） */
    public const COMPETITION_COLUMNS = [
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
    ];

    public const COMPETITION_HEADINGS = [
        '比賽名稱',
        '比賽日期',
        '積分表',
        '中文名',
        '外文名',
        '證件號碼',
        '組別',
        '量級',
        '角色',
        '名次',
        '分數',
    ];

    public const COMPETITION_WIDTHS = [
        'A' => 34, 'B' => 14, 'C' => 30, 'D' => 14, 'E' => 18, 'F' => 16,
        'G' => 16, 'H' => 12, 'I' => 12, 'J' => 12, 'K' => 10,
    ];

    /** 集訓記錄（對應 App\Imports\TrainingRecordImport） */
    public const TRAINING_COLUMNS = [
        'month',
        'member_number',
        'member_name',
        'centre_count',
        'win_count',
        'session_count',
        'competition_day_count',
    ];

    public const TRAINING_HEADINGS = [
        '月份（YYYY-MM）',
        '會員號碼',
        '會員姓名（沒有號碼時用）',
        '中心/集訓',
        '得勝',
        '集訓(節數)',
        '比賽日數',
    ];

    public const TRAINING_WIDTHS = [
        'A' => 16, 'B' => 14, 'C' => 26, 'D' => 12, 'E' => 10, 'F' => 12, 'G' => 12,
    ];

    private $ranking;

    private array $rankLabels;

    private array $roleLabels;

    private bool $includeTraining;

    /**
     * @param  \Illuminate\Support\Collection  $ranking  MemberPointController 的排名資料
     * @param  array  $rankLabels  名次代號 => 名稱（first => 第一名）
     * @param  array  $roleLabels  角色代號 => 名稱（athlete => 運動員）
     * @param  bool  $includeTraining  是否包含集訓記錄工作表
     */
    public function __construct(
        $ranking,
        array $rankLabels = [],
        array $roleLabels = [],
        bool $includeTraining = true
    ) {
        $this->ranking = $ranking;
        $this->rankLabels = $rankLabels;
        $this->roleLabels = $roleLabels;
        $this->includeTraining = $includeTraining;
    }

    public function sheets(): array
    {
        $sheets = [
            new MemberPointSheet(
                '比賽積分',
                self::COMPETITION_HEADINGS,
                self::COMPETITION_COLUMNS,
                $this->competitionRows(),
                self::COMPETITION_WIDTHS
            ),
        ];

        if ($this->includeTraining) {
            $sheets[] = new MemberPointSheet(
                '集訓記錄',
                self::TRAINING_HEADINGS,
                self::TRAINING_COLUMNS,
                $this->trainingRows(),
                self::TRAINING_WIDTHS
            );
        }

        return $sheets;
    }

    /**
     * 一行 = 一位選手在一場比賽的成績。
     */
    private function competitionRows(): array
    {
        $rows = [];

        foreach ($this->ranking as $row) {
            foreach ($row['results'] ?? [] as $result) {
                $rank = $result['result_rank'] ?? null;

                $rows[] = [
                    $result['competition_title'] ?? '',
                    $result['date'] ?? '',
                    $result['score_scheme'] ?? '',
                    $row['name_zh'] ?? '',
                    $row['name_fn'] ?? '',
                    $row['id_num'] ?? '',
                    $result['category'] ?? '',
                    $result['weight'] ?? '',
                    $this->roleLabels[$result['role'] ?? ''] ?? ($result['role'] ?? ''),
                    $rank === null ? '' : ($this->rankLabels[$rank] ?? $rank),
                    $result['result_score'] ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * 一行 = 一位運動員一個月的集訓記錄。
     */
    private function trainingRows(): array
    {
        $rows = [];

        foreach ($this->ranking as $row) {
            foreach ($row['training'] ?? [] as $training) {
                $rows[] = [
                    $training['month'] ?? '',
                    $row['member_number'] ?? '',
                    $row['name'] ?? '',
                    $training['centre_count'] ?? 0,
                    $training['win_count'] ?? 0,
                    $training['session_count'] ?? 0,
                    $training['competition_day_count'] ?? 0,
                ];
            }
        }

        return $rows;
    }
}
