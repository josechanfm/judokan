<?php

namespace App\Http\Controllers\Organization;

use App\Exports\TrainingRecordExport;
use App\Http\Controllers\Controller;
use App\Imports\TrainingRecordImport;
use App\Models\AthleteSetting;
use App\Models\Organization;
use App\Models\TrainingRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 每月運動員訓練記錄（中心/集訓、得勝、集訓節數、比賽日數）。
 */
class TrainingRecordController extends Controller
{
    /**
     * 指定月份的集訓隊成員記錄表。
     */
    public function index(Request $request)
    {
        $organizationId = session('organization')->id;
        $month = $this->resolveMonth($request->input('month'));

        return Inertia::render('Organization/TrainingRecords', [
            'rows' => $this->buildRows($organizationId, $month),
            'month' => $month->format('Y-m'),
            'rates' => TrainingRecord::ratesFor($organizationId),
        ]);
    }

    /**
     * 一次儲存整張表（四項次數全為 0 的列會刪除記錄）。
     */
    public function save(Request $request)
    {
        $organizationId = session('organization')->id;

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'records' => 'array',
            'records.*.member_id' => 'required|integer',
            'records.*.centre_count' => 'nullable|integer|min:0',
            'records.*.win_count' => 'nullable|integer|min:0',
            'records.*.session_count' => 'nullable|integer|min:0',
            'records.*.competition_day_count' => 'nullable|integer|min:0',
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'])
            ->startOfMonth()
            ->format('Y-m-d');

        $allowed = array_flip(AthleteSetting::athleteIds($organizationId));

        foreach ($validated['records'] ?? [] as $row) {
            $memberId = (int) $row['member_id'];

            if (! isset($allowed[$memberId])) {
                continue;
            }

            $counts = [];

            foreach (TrainingRecord::POINT_FIELDS as $field) {
                $counts[$field] = max(0, (int) ($row[$field] ?? 0));
            }

            $where = [
                'organization_id' => $organizationId,
                'member_id' => $memberId,
            ];

            // 四項都是 0 就不用留記錄
            if (array_sum($counts) === 0) {
                TrainingRecord::where($where)->whereDate('month', $month)->delete();

                continue;
            }

            TrainingRecord::updateOrCreate(
                $where + ['month' => $month],
                $counts
            );
        }

        return redirect()->back();
    }

    /**
     * 儲存四項的計分設定。
     */
    public function rates(Request $request)
    {
        $data = $request->validate([
            'centre_count' => 'required|integer|min:0',
            'win_count' => 'required|integer|min:0',
            'session_count' => 'required|integer|min:0',
            'competition_day_count' => 'required|integer|min:0',
        ]);

        TrainingRecord::storeRates(session('organization')->id, $data);

        return redirect()->back();
    }

    /**
     * 匯出指定月份的名單。
     */
    public function export(Request $request)
    {
        $organizationId = session('organization')->id;
        $month = $this->resolveMonth($request->input('month'));

        return Excel::download(
            new TrainingRecordExport($this->buildRows($organizationId, $month), $month->format('Y-m')),
            'training-records-'.$month->format('Ym').'.xlsx'
        );
    }

    /**
     * 從 Excel 匯入每月記錄。回傳 { imported, errors }。
     *
     * 範本：public/templates/training_record_list.xlsx
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new TrainingRecordImport(session('organization'));

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    /**
     * 產生畫面用的列（每位運動員一列）。
     */
    private function buildRows(int $organizationId, Carbon $month): array
    {
        $rates = TrainingRecord::ratesFor($organizationId);
        $records = TrainingRecord::where('organization_id', $organizationId)
            ->whereDate('month', $month)
            ->get()
            ->keyBy('member_id');

        return $this->members($organizationId)
            ->map(function ($member) use ($records, $rates) {
                $record = $records->get($member->id);

                $row = [
                    'member_id' => $member->id,
                    'name' => $member->display_name ?: ($member->name_zh ?: $member->name_fn),
                    'member_number' => $member->member_number,
                    'is_team_member' => (bool) $member->is_team_member,
                    'is_club_athlete' => (bool) $member->is_club_athlete,
                    'athlete_type' => $this->athleteType(
                        (bool) $member->is_team_member,
                        (bool) $member->is_club_athlete
                    ),
                ];

                $points = 0;

                foreach (TrainingRecord::POINT_FIELDS as $field) {
                    $row[$field] = $record ? (int) $record->{$field} : 0;
                    $points += $row[$field] * (int) ($rates[$field] ?? 0);
                }

                $row['points'] = $points;

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * 集訓記錄會列出本屬會所有運動員（集訓隊成員 + 本館運動員）。
     */
    private function members(int $organizationId)
    {
        $athleteIds = AthleteSetting::athleteIds($organizationId);

        if ($athleteIds === []) {
            return collect();
        }

        $settings = AthleteSetting::where('organization_id', $organizationId)
            ->whereIn('member_id', $athleteIds)
            ->get()
            ->keyBy('member_id');

        return Organization::find($organizationId)
            ->members()
            ->whereIn('members.id', $athleteIds)
            ->get()
            ->each(function ($member) use ($settings) {
                $setting = $settings->get($member->id);
                $member->is_team_member = (bool) ($setting->is_team_member ?? false);
                $member->is_club_athlete = (bool) ($setting->is_club_athlete ?? false);
            })
            // 先按類型排：集訓隊在上，然後本館運動員，同類型再按姓名
            ->sortBy([
                fn ($a, $b) => (int) $b->is_team_member <=> (int) $a->is_team_member,
                fn ($a, $b) => (int) $b->is_club_athlete <=> (int) $a->is_club_athlete,
                fn ($a, $b) => strcmp(
                    mb_strtolower($a->display_name ?: ($a->name_zh ?: (string) $a->name_fn)),
                    mb_strtolower($b->display_name ?: ($b->name_zh ?: (string) $b->name_fn))
                ),
            ])
            ->values();
    }

    /**
     * 運動員類別文字：集訓隊 / 本館運動員 / 集訓隊＋本館。
     */
    private function athleteType(bool $isTeamMember, bool $isClubAthlete): string
    {
        if ($isTeamMember && $isClubAthlete) {
            return '集訓隊＋本館';
        }

        if ($isTeamMember) {
            return '集訓隊';
        }

        return $isClubAthlete ? '本館運動員' : '';
    }

    private function resolveMonth(?string $value): Carbon
    {
        if ($value && preg_match('/^(\d{4})-(\d{1,2})$/', trim($value), $matches)) {
            $month = (int) $matches[2];

            if ($month >= 1 && $month <= 12) {
                return Carbon::createFromDate((int) $matches[1], $month, 1)->startOfMonth();
            }
        }

        return Carbon::today()->startOfMonth();
    }
}
