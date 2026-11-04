<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Exports\CompetitionScoreExport;
use App\Imports\CompetitionScoreImport;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\CompetitionScore;
use App\Models\Config;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionScoreController extends Controller
{
    /**
     * 積分表的欄位（名次代號）。
     */
    private const FIELDS = ['first', 'second', 'third', 'fifth', 'seventh', 'advance', 'participate'];

    /**
     * 賽事積分表管理頁。
     *
     * organization_id = 0 表示共用積分表（建立賽事時可選用的就是這些）。
     */
    public function index()
    {
        $scoreCategories = collect(Config::item('competition_score_categories') ?: []);
        $order = $scoreCategories->pluck('value')->flip();

        $scores = CompetitionScore::where('organization_id', 0)
            ->withCount('competitions')
            ->get()
            ->sortBy(fn ($score) => [
                $order[$score->category] ?? PHP_INT_MAX,
                $score->id,
            ])
            ->values();

        return Inertia::render('Organization/CompetitionScores', [
            'competitionScores' => $scores,
            'competitionScoreCategories' => $scoreCategories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['organization_id'] = 0;

        CompetitionScore::create($data);

        return redirect()->back();
    }

    /**
     * 匯出積分表。格式與匯入範本相同，可直接再匯入。
     */
    public function export()
    {
        $scoreCategories = collect(Config::item('competition_score_categories') ?: []);
        $order = $scoreCategories->pluck('value')->flip();

        $scores = CompetitionScore::where('organization_id', 0)
            ->get()
            ->sortBy(fn ($score) => [
                $order[$score->category] ?? PHP_INT_MAX,
                $score->id,
            ])
            ->values();

        return Excel::download(
            new CompetitionScoreExport(
                $scores->all(),
                $scoreCategories->pluck('value')->all()
            ),
            'competition-scores-'.now()->format('Ymd').'.xlsx'
        );
    }

    /**
     * 從 Excel 匯入積分表。回傳 { imported, errors }。
     *
     * 範本：public/templates/competition_score_list.xlsx
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new CompetitionScoreImport();

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    public function update(Request $request, CompetitionScore $competitionScore)
    {
        $data = $this->validated($request);
        $competitionScore->update($data);

        // 可選：依新的積分重算已使用此積分表的賽事成績
        if ($request->boolean('sync_results')) {
            $this->syncResults($competitionScore->fresh());
        }

        return redirect()->back();
    }

    public function destroy(CompetitionScore $competitionScore)
    {
        if ($competitionScore->competitions()->exists()) {
            return redirect()->back()->withErrors([
                'competition_score' => '此積分表已被賽事使用，無法刪除',
            ]);
        }

        $competitionScore->delete();

        return redirect()->back();
    }

    /**
     * 依積分表重算成績：
     * 1. 更新賽事上的積分快照（competitions.result_scores）
     * 2. 更新已記錄成績的 result_score
     */
    private function syncResults(CompetitionScore $score): int
    {
        $scores = [];
        foreach (self::FIELDS as $field) {
            $scores[$field] = $score->{$field};
        }

        $count = 0;

        Competition::where('competition_score_id', $score->id)->each(function (Competition $competition) use ($scores, &$count) {
            $competition->result_scores = $scores;
            $competition->save();

            foreach ($scores as $rank => $value) {
                CompetitionApplication::where('competition_id', $competition->id)
                    ->where('result_rank', $rank)
                    ->update(['result_score' => $value]);
            }

            $count++;
        });

        return $count;
    }

    /**
     * 驗證並整理表單資料。
     */
    private function validated(Request $request): array
    {
        $rules = [
            'title' => 'required|string|max:191',
            'category' => 'required|string|max:50',
        ];

        foreach (self::FIELDS as $field) {
            $rules[$field] = 'nullable|integer|min:0|max:100000';
        }

        $data = $request->validate($rules);

        foreach (self::FIELDS as $field) {
            $data[$field] = isset($data[$field]) ? (int) $data[$field] : 0;
        }

        return $data;
    }
}
