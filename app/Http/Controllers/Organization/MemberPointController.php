<?php

namespace App\Http\Controllers\Organization;

use App\Exports\MemberPointExport;
use App\Http\Controllers\Controller;
use App\Imports\MemberPointImport;
use App\Models\AthleteSetting;
use App\Models\CompetitionScore;
use App\Models\CompetitionApplication;
use App\Models\Config;
use App\Models\Member;
use App\Models\TrainingRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MemberPointController extends Controller
{
    /**
     * 會員比賽成績積分統計。
     *
     * 統計本屬會選手在指定期間內所有比賽的 result_score 加總，
     * 預設期間為最近兩年，可自選起訖日期。
     */
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        return Inertia::render('Organization/MemberPoints', [
            'ranking' => $this->buildRanking($filters),
            'competitionResults' => Config::item('competition_results'),
            'competitionRoles' => Config::item('competition_roles'),
            'filters' => $filters,
        ]);
    }

    /**
     * 匯出目前篩選條件下的積分統計（可直接再匯入）。
     *
     * type = all（預設）→ 比賽積分 + 集訓記錄
     * type = competition → 只有比賽積分
     */
    public function export(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $ranking = $this->buildRanking($filters);

        $rankLabels = collect(Config::item('competition_results') ?: [])->pluck('label', 'value')->all();
        $roleLabels = collect(Config::item('competition_roles') ?: [])->pluck('label', 'value')->all();

        $type = $request->input('type') === 'competition' ? 'competition' : 'all';

        return Excel::download(
            new MemberPointExport($ranking, $rankLabels, $roleLabels, $type === 'all'),
            'member-points-'.$type.'-'.Carbon::today()->format('Ymd').'.xlsx'
        );
    }

    /**
     * 匯入積分統計檔案（比賽積分 + 集訓記錄）。回傳 { imported, errors }。
     *
     * 可以匯入本頁匯出的檔案，或成績範本 result_list.xlsx。
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new MemberPointImport(session('organization'));

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'competition_imported' => $import->competitionCount(),
            'training_imported' => $import->trainingCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    /**
     * 統計條件（預設近兩年）。
     */
    private function resolveFilters(Request $request): array
    {
        $end = $request->input('end') ?: Carbon::today()->format('Y-m-d');
        // 預設由「上一年 1 月 1 日」開始統計
        $start = $request->input('start')
            ?: Carbon::parse($end)->startOfYear()->subYear()->format('Y-m-d');

        return [
            'start' => $start,
            'end' => $end,
            'keyword' => trim((string) $request->input('keyword', '')),
            'athlete_scope' => in_array($request->input('athlete_scope'), ['club', 'team'], true)
                ? $request->input('athlete_scope')
                : 'all',
        ];
    }

    /**
     * 本館運動員 + 集訓隊成員的會員 id。
     *
     * @param  string  $scope  all（兩者都要）| club（只要本館運動員）| team（只要集訓隊成員）
     */
    private function athleteIds(int $organizationId, string $scope = 'all'): array
    {
        $query = AthleteSetting::where('organization_id', $organizationId);

        if ($scope === 'club') {
            $query->where('is_club_athlete', true);
        } elseif ($scope === 'team') {
            $query->where('is_team_member', true);
        } else {
            $query->where(function ($sub) {
                $sub->where('is_club_athlete', true)
                    ->orWhere('is_team_member', true);
            });
        }

        return $query->pluck('member_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * 依統計條件產生排行資料。
     *
     * 只統計「本館運動員」及「集訓隊成員」（見運動員設定），
     * 其他會員及公開賽的臨時報名都不列入。
     */
    private function buildRanking(array $filters)
    {
        $organization = session('organization');
        $start = $filters['start'];
        $end = $filters['end'];
        $keyword = $filters['keyword'];

        $athleteIds = $this->athleteIds($organization->id, $filters['athlete_scope'] ?? 'all');

        if ($athleteIds === []) {
            return collect();
        }

        $applications = CompetitionApplication::query()
            ->join('competitions', 'competitions.id', '=', 'competition_applications.competition_id')
            ->where('competition_applications.organization_id', $organization->id)
            ->whereIn('competition_applications.member_id', $athleteIds)
            ->whereNotNull('competition_applications.result_score')
            ->whereDate('competitions.start_date', '>=', $start)
            ->whereDate('competitions.start_date', '<=', $end)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('competition_applications.name_zh', 'like', "%{$keyword}%")
                        ->orWhere('competition_applications.name_fn', 'like', "%{$keyword}%")
                        ->orWhere('competition_applications.display_name', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('competitions.start_date')
            ->get([
                'competition_applications.id',
                'competition_applications.member_id',
                'competition_applications.name_zh',
                'competition_applications.name_fn',
                'competition_applications.display_name',
                'competition_applications.id_num',
                'competition_applications.role',
                'competition_applications.category',
                'competition_applications.weight',
                'competition_applications.result_rank',
                'competition_applications.result_score',
                'competitions.id as competition_id',
                'competitions.title_zh as competition_title',
                'competitions.start_date as competition_date',
                'competitions.competition_score_id as competition_score_id',
            ]);

        $scoreTitles = CompetitionScore::pluck('title', 'id')->all();

        $grouped = [];
        foreach ($applications as $application) {
            // 有會員資料就用會員分組；公開賽（沒有 member_id）用證件號碼，
            // 再沒有就用姓名，完全沒有識別資料時單獨列一筆，避免不同人併成同一列
            if ($application->member_id) {
                $key = 'member-'.$application->member_id;
            } elseif ($application->id_num) {
                $key = 'guest-id-'.$application->id_num;
            } elseif ($application->name_zh || $application->name_fn) {
                $key = 'guest-name-'.$application->name_zh.'|'.$application->name_fn;
            } else {
                $key = 'guest-app-'.$application->id;
            }

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'member_id' => $application->member_id,
                    'name' => $application->display_name ?: ($application->name_zh ?: $application->name_fn),
                    'name_zh' => $application->name_zh,
                    'name_fn' => $application->name_fn,
                    'id_num' => $application->id_num,
                    'member_number' => null,
                    'is_member' => ! is_null($application->member_id),
                    'total_score' => 0,
                    'competition_ids' => [],
                    'results' => [],
                    'training' => [],
                    'training_count' => 0,
                    'training_score' => 0,
                ];
            }

            $grouped[$key]['total_score'] += (int) $application->result_score;
            $grouped[$key]['competition_ids'][$application->competition_id] = true;
            $grouped[$key]['results'][] = [
                'competition_id' => $application->competition_id,
                'competition_title' => $application->competition_title,
                'date' => $application->competition_date,
                'score_scheme' => $scoreTitles[$application->competition_score_id] ?? null,
                'role' => $application->role,
                'category' => $application->category,
                'weight' => $application->weight,
                'result_rank' => $application->result_rank,
                'result_score' => (int) $application->result_score,
            ];
        }

        // 每月訓練記錄：四項次數 × 各項設定分數
        $rates = TrainingRecord::ratesFor($organization->id);

        $trainingRecords = TrainingRecord::query()
            ->join('members', 'members.id', '=', 'training_records.member_id')
            ->where('training_records.organization_id', $organization->id)
            ->whereIn('training_records.member_id', $athleteIds)
            ->whereDate('training_records.month', '>=', $start)
            ->whereDate('training_records.month', '<=', $end)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('members.name_zh', 'like', "%{$keyword}%")
                        ->orWhere('members.name_fn', 'like', "%{$keyword}%")
                        ->orWhere('members.display_name', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('training_records.month')
            ->get([
                'training_records.member_id',
                'training_records.month',
                'training_records.centre_count',
                'training_records.win_count',
                'training_records.session_count',
                'training_records.competition_day_count',
                'members.name_zh',
                'members.name_fn',
                'members.display_name',
            ]);

        foreach ($trainingRecords as $record) {
            $key = 'member-'.$record->member_id;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'member_id' => $record->member_id,
                    'name' => $record->display_name ?: ($record->name_zh ?: $record->name_fn),
                    'name_zh' => $record->name_zh,
                    'name_fn' => $record->name_fn,
                    'id_num' => null,
                    'member_number' => null,
                    'is_member' => true,
                    'total_score' => 0,
                    'competition_ids' => [],
                    'results' => [],
                    'training' => [],
                    'training_count' => 0,
                    'training_score' => 0,
                ];
            }

            $points = 0;

            foreach (TrainingRecord::POINT_FIELDS as $field) {
                $points += (int) $record->{$field} * (int) ($rates[$field] ?? 0);
            }

            $grouped[$key]['training'][] = [
                'month' => Carbon::parse($record->month)->format('Y-m'),
                'centre_count' => (int) $record->centre_count,
                'win_count' => (int) $record->win_count,
                'session_count' => (int) $record->session_count,
                'competition_day_count' => (int) $record->competition_day_count,
                'points' => $points,
            ];
            $grouped[$key]['training_count']++;
            $grouped[$key]['training_score'] += $points;
        }

        // 補上會員號碼
        $memberIds = collect($grouped)->pluck('member_id')->filter()->values();
        if ($memberIds->isNotEmpty()) {
            $members = Member::whereIn('id', $memberIds)->get()->keyBy('id');
            foreach ($grouped as $key => $row) {
                if ($row['member_id'] && isset($members[$row['member_id']])) {
                    $grouped[$key]['member_number'] = $members[$row['member_id']]->member_number;
                }
            }
        }

        return collect($grouped)
            ->map(function ($row) {
                $row['competition_count'] = count($row['competition_ids']);
                unset($row['competition_ids']);

                $row['competition_score'] = (int) $row['total_score'];
                $row['total_score'] = $row['competition_score'] + (int) ($row['training_score'] ?? 0);

                return $row;
            })
            ->sortByDesc('total_score')
            ->values();
    }
}
