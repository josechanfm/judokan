<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Config;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Services\CompetitionCategoryNormalizer;
use App\Exports\CompetitionResultExport;
use App\Imports\CompetitionResultImport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionResultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Competition $competition)
    {
        // 保險：有些舊賽事沒有組別設定（或有報名但組別是舊格式），
        // 用報名資料自動建立／對齊組別與量級，賽果頁才選得到選手
        if (empty($this->decodeJson($competition->categories_weights)) && $competition->applications()->exists()) {
            (new CompetitionCategoryNormalizer())->normalize($competition);
        }

        $competition->applications;
        $competition->score;
        // competitions.categories_weights / roles 在資料庫是（雙重編碼的）JSON，先解碼給前端用
        $competition->categories_weights = $this->decodeJson($competition->categories_weights);
        $competition->roles = $this->decodeJson($competition->roles);

        return Inertia::render('Organization/CompetitionResults',[
            'competitionResults'=>Config::item('competition_results'),
            'competition'=>$competition
        ]);
    }

    /**
     * 資料庫裡的 JSON 欄位可能是雙重編碼，這裡一律正規化成陣列／物件。
     */
    private function decodeJson($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded);
            }

            return $decoded;
        }

        return $value;
    }

    /**
     * 匯出這場賽事的賽果（欄位與匯入範本相同，可直接修改後再匯入）。
     */
    public function export(Competition $competition)
    {
        $competition->load('score');

        $categories = $this->decodeJson($competition->categories_weights) ?: [];
        $roles = $this->decodeJson($competition->roles) ?: [];
        $rankOptions = collect(Config::item('competition_results') ?: [])
            ->map(fn ($option) => (array) $option)
            ->all();

        $rankLabels = collect($rankOptions)->pluck('label', 'value')->all();
        $roleLabels = collect($roles)
            ->map(fn ($role) => (array) $role)
            ->pluck('label', 'value')
            ->all();

        $scores = $competition->score?->toArray();
        if (! $scores) {
            $fallback = $this->decodeJson($competition->result_scores);
            $scores = is_array($fallback) ? $fallback : [];
        }

        $applications = $competition->applications()
            ->orderBy('category')
            ->orderBy('gender')
            ->orderBy('weight')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($applications as $application) {
            $rows[] = [
                $competition->title_zh ?: $competition->title_fn,
                $competition->start_date,
                $competition->score->title ?? '',
                $application->name_zh,
                $application->name_fn,
                $application->id_num,
                $this->categoryName($categories, $application->category),
                $this->weightName($categories, $application),
                $roleLabels[$application->role] ?? $application->role,
                $rankLabels[$application->result_rank] ?? null,
                $application->result_score,
            ];
        }

        return Excel::download(
            new CompetitionResultExport($competition, $rows, $rankOptions, $scores),
            'competition-results-'.$competition->id.'-'.Carbon::today()->format('Ymd').'.xlsx'
        );
    }

    /**
     * 從 Excel 匯入這場賽事的賽果。回傳 { imported, created, errors }。
     */
    public function import(Request $request, Competition $competition)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new CompetitionResultImport($competition);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return response()->json([
                'imported' => $import->importedCount(),
                'created' => $import->createdCount(),
                'errors' => [],
                'message' => '匯入失敗：'.$e->getMessage(),
            ], 422);
        }

        return response()->json([
            'imported' => $import->importedCount(),
            'created' => $import->createdCount(),
            'errors' => $import->failures()->values(),
        ]);
    }

    private function categoryName(array $categories, $code): string
    {
        foreach ($categories as $category) {
            $category = (array) $category;
            if ((string) ($category['code'] ?? '') === (string) $code) {
                return (string) ($category['name'] ?? $code);
            }
        }

        return (string) $code;
    }

    private function weightName(array $categories, CompetitionApplication $application): string
    {
        $lists = $application->gender === 'F' ? ['female', 'male'] : ['male', 'female'];

        foreach ($categories as $category) {
            $category = (array) $category;
            if ((string) ($category['code'] ?? '') !== (string) $application->category) {
                continue;
            }

            foreach ($lists as $listKey) {
                foreach ((array) ($category[$listKey] ?? []) as $weight) {
                    $weight = (array) $weight;
                    if ((string) ($weight['code'] ?? '') === (string) $application->weight) {
                        return (string) ($weight['name'] ?? $application->weight);
                    }
                }
            }
        }

        return (string) $application->weight;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Competition $competition)
    {
        $cgw=$request->cgw;
        $results=$request->results;

        // 積分對照表：比賽建立時就把 competition_scores 快照到 result_scores
        $scores=$competition->score?->toArray() ?? $competition->result_scores ?? [];

        // 先清掉「這場比賽、這個組別/性別/量級」原本的成績
        // （原本的寫法少了 competition_id，會把其他比賽的成績一起清掉）
        CompetitionApplication::where('competition_id',$competition->id)
        ->where('category',$cgw[0])
        ->where('gender',$cgw[1])
        ->where('weight',$cgw[2])
        ->update([
            'result_rank'=>null,
            'result_score'=>null
        ]);

        foreach($results as $result){
            if(empty($result['application_id'])){
                continue;
            }

            $ids=is_array($result['application_id'])
                ? $result['application_id']
                : [$result['application_id']];

            CompetitionApplication::where('competition_id',$competition->id)
            ->whereIn('id',$ids)
            ->update([
                'result_rank'=>$result['value'],
                'result_score'=>$scores[$result['value']] ?? null
            ]);
        }

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
