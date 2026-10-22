<?php

namespace App\Http\Controllers\Organization;

use App\Exports\MemberPointExport;
use App\Http\Controllers\Controller;
use App\Imports\ResultImport;
use App\Models\CompetitionApplication;
use App\Models\Config;
use App\Models\Member;
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
     * 匯出目前篩選條件下的積分統計（排行 + 成績明細）。
     */
    public function export(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $ranking = $this->buildRanking($filters);

        $rankLabels = collect(Config::item('competition_results') ?: [])->pluck('label', 'value')->all();
        $roleLabels = collect(Config::item('competition_roles') ?: [])->pluck('label', 'value')->all();

        return Excel::download(
            new MemberPointExport($ranking, $filters, $rankLabels, $roleLabels),
            'member-points-'.Carbon::today()->format('Ymd').'.xlsx'
        );
    }

    /**
     * 從 Excel 匯入比賽成績（前端「匯入成績」）。回傳 { imported, errors }。
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new ResultImport(session('organization'));

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    /**
     * 統計條件（預設近兩年）。
     */
    private function resolveFilters(Request $request): array
    {
        $end = $request->input('end') ?: Carbon::today()->format('Y-m-d');
        $start = $request->input('start')
            ?: Carbon::parse($end)->subYears(2)->addDay()->format('Y-m-d');

        return [
            'start' => $start,
            'end' => $end,
            'role' => $request->input('role') ?: null,
            'keyword' => trim((string) $request->input('keyword', '')),
        ];
    }

    /**
     * 依統計條件產生排行資料（會員 + 沒有會員資料的公開賽報名）。
     */
    private function buildRanking(array $filters)
    {
        $organization = session('organization');
        $start = $filters['start'];
        $end = $filters['end'];
        $role = $filters['role'];
        $keyword = $filters['keyword'];

        $applications = CompetitionApplication::query()
            ->join('competitions', 'competitions.id', '=', 'competition_applications.competition_id')
            ->where('competition_applications.organization_id', $organization->id)
            ->whereNotNull('competition_applications.result_score')
            ->whereDate('competitions.start_date', '>=', $start)
            ->whereDate('competitions.start_date', '<=', $end)
            ->when($role, function ($query) use ($role) {
                $query->where('competition_applications.role', $role);
            })
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
            ]);

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
                ];
            }

            $grouped[$key]['total_score'] += (int) $application->result_score;
            $grouped[$key]['competition_ids'][$application->competition_id] = true;
            $grouped[$key]['results'][] = [
                'competition_id' => $application->competition_id,
                'competition_title' => $application->competition_title,
                'date' => $application->competition_date,
                'role' => $application->role,
                'category' => $application->category,
                'weight' => $application->weight,
                'result_rank' => $application->result_rank,
                'result_score' => (int) $application->result_score,
            ];
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

                return $row;
            })
            ->sortByDesc('total_score')
            ->values();
    }
}
