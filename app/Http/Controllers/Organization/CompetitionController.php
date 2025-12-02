<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\CompetitionScore;
use App\Models\Config;
use Illuminate\Support\Facades\Validator;

class CompetitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Organization/Competitions', [
            'competitions' => Competition::where('organization_id', session('organization')->id)
                ->with('score')
                ->latest()
                ->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Organization/Competition', [
            'categories_weights' => Config::items('categories_weights', null),
            'competitionScores' => CompetitionScore::where('organization_id', 0)->get(),
            'staffOptions' => Config::item('staff_options'),
            'refereeOptions' => Config::item('referee_options'),
            'roles' => Config::item('competition_roles')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 驗證輸入資料
        $validator = Validator::make($request->all(), [
            'title_zh' => 'required|string|max:255',
            'competition_score_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'fee' => 'nullable|numeric|min:0',
            'published' => 'boolean',
            'scope' => 'required|in:PUB,MJA,ORG',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 準備資料
        $data = $request->except(['banner', 'attachment', 'athlete_card']);
        $data['organization_id'] = session('organization')->id;
        
        // 處理 JSON 資料
        if ($request->has('match_dates')) {
            $data['match_dates'] = is_array($request->match_dates) 
                ? json_encode($request->match_dates) 
                : $request->match_dates;
        }
        
        if ($request->has('cwSelected')) {
            $data['categories_weights'] = is_array($request->cwSelected)
                ? json_encode($request->cwSelected)
                : $request->cwSelected;
        }
        
        if ($request->has('roleSelected')) {
            $data['roles'] = is_array($request->roleSelected)
                ? json_encode($request->roleSelected)
                : $request->roleSelected;
        }
        
        if ($request->has('refereeOptionsSelected')) {
            $data['referee_options'] = is_array($request->refereeOptionsSelected)
                ? json_encode($request->refereeOptionsSelected)
                : $request->refereeOptionsSelected;
        }
        
        if ($request->has('staffOptionsSelected')) {
            $data['staff_options'] = is_array($request->staffOptionsSelected)
                ? json_encode($request->staffOptionsSelected)
                : $request->staffOptionsSelected;
        }

        // 創建賽事
        $competition = Competition::create($data);

        // 處理檔案上傳
        $this->handleFileUploads($request, $competition);

        return redirect()->route('manage.competitions.index')
            ->with('success', '賽事創建成功');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Competition  $competition
     * @return \Illuminate\Http\Response
     */
    public function show(Competition $competition)
    {
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index')
                ->with('error', '無權限查看此賽事');
        }
        
        $competition->load('media', 'score');
        
        return Inertia::render('Organization/CompetitionShow', [
            'competition' => $competition,
            'categories_weights' => Config::items('categories_weights', 0),
            'roles' => Config::item('competition_roles')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Competition  $competition
     * @return \Illuminate\Http\Response
     */
    public function edit(Competition $competition)
    {
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index')
                ->with('error', '無權限編輯此賽事');
        }
        
        $competition->load('media', 'score');
        
        // 解碼 JSON 欄位以便前端使用
        if ($competition->match_dates) {
            $competition->match_dates = json_decode($competition->match_dates, true);
        }
        
        if ($competition->categories_weights) {
            $competition->categories_weights = json_decode($competition->categories_weights, true);
        }
        
        if ($competition->roles) {
            $competition->roles = json_decode($competition->roles, true);
        }
        
        if ($competition->referee_options) {
            $competition->referee_options = json_decode($competition->referee_options, true);
        }
        
        if ($competition->staff_options) {
            $competition->staff_options = json_decode($competition->staff_options, true);
        }

        return Inertia::render('Organization/Competition', [
            'competition' => $competition,
            'competitionScores' => CompetitionScore::where('organization_id', 0)->get(),
            'scoreCategories' => Config::item('competition_score_categories'),
            'categories_weights' => Config::items('categories_weights', 0),
            'staffOptions' => Config::item('staff_options'),
            'refereeOptions' => Config::item('referee_options'),
            'roles' => Config::item('competition_roles')
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Competition  $competition
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Competition $competition)
    {
        // 檢查權限
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index')
                ->with('error', '無權限更新此賽事');
        }

        // 驗證輸入資料
        $validator = Validator::make($request->all(), [
            'title_zh' => 'required|string|max:255',
            'competition_score_id' => 'nullable|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'fee' => 'nullable|numeric|min:0',
            'published' => 'boolean',
            'scope' => 'required|in:PUB,MJA,ORG',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 準備更新資料
        $updateData = $request->except(['banner', 'attachment', 'athlete_card']);
        
        // 處理 JSON 資料
        if ($request->has('match_dates')) {
            $updateData['match_dates'] = is_array($request->match_dates) 
                ? json_encode($request->match_dates) 
                : $request->match_dates;
        }
        
        if ($request->has('cwSelected')) {
            $updateData['categories_weights'] = is_array($request->cwSelected)
                ? json_encode($request->cwSelected)
                : $request->cwSelected;
        }
        
        if ($request->has('roleSelected')) {
            $updateData['roles'] = is_array($request->roleSelected)
                ? json_encode($request->roleSelected)
                : $request->roleSelected;
        }
        
        if ($request->has('refereeOptionsSelected')) {
            $updateData['referee_options'] = is_array($request->refereeOptionsSelected)
                ? json_encode($request->refereeOptionsSelected)
                : $request->refereeOptionsSelected;
        }
        
        if ($request->has('staffOptionsSelected')) {
            $updateData['staff_options'] = is_array($request->staffOptionsSelected)
                ? json_encode($request->staffOptionsSelected)
                : $request->staffOptionsSelected;
        }

        // 更新賽事資料
        $competition->update($updateData);
        
        // 更新積分資料
        if ($competition->score) {
            $competition->result_scores = $competition->score->toArray();
            $competition->save();
        }

        // 處理檔案上傳
        $this->handleFileUploads($request, $competition);

        return redirect()->route('manage.competitions.index')
            ->with('success', '賽事更新成功');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Competition  $competition
     * @return \Illuminate\Http\Response
     */
    public function destroy(Competition $competition)
    {
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index')
                ->with('error', '無權限刪除此賽事');
        }

        // 刪除相關媒體檔案
        $competition->clearMediaCollection('competitionBanner');
        $competition->clearMediaCollection('competitionAttachment');
        $competition->clearMediaCollection('competitionAthleteCard');
        
        // 刪除賽事
        $competition->delete();

        return redirect()->route('manage.competitions.index')
            ->with('success', '賽事刪除成功');
    }

    /**
     * 處理檔案上傳
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Competition  $competition
     * @return void
     */
    private function handleFileUploads(Request $request, Competition $competition)
    {
        // 處理 banner 檔案
        if ($request->hasFile('banner')) {
            $bannerFile = $request->file('banner');
            // 清除舊的 banner (如果需要替換)
            $competition->clearMediaCollection('competitionBanner');
            $competition->addMedia($bannerFile)
                ->toMediaCollection('competitionBanner');
        }

        // 處理 attachment 檔案
        if ($request->hasFile('attachment')) {
            // 使用 Laravel Media Library 的方式處理多個檔案
            $attachments = $request->file('attachment');
            if (!is_array($attachments)) {
                $attachments = [$attachments];
            }
            
            foreach ($attachments as $file) {
                if ($file && $file->isValid()) {
                    $competition->addMedia($file)
                        ->toMediaCollection('competitionAttachment');
                }
            }
        }

        // 處理 athlete_card 檔案 (修正：欄位名稱應該是 athlete_card，不是 athleteCard)
        if ($request->hasFile('athlete_card')) {
            $athleteCards = $request->file('athlete_card');
            if (!is_array($athleteCards)) {
                $athleteCards = [$athleteCards];
            }
            
            foreach ($athleteCards as $file) {
                if ($file && $file->isValid()) {
                    $competition->addMedia($file)
                        ->toMediaCollection('competitionAthleteCard');
                }
            }
        }
    }

    /**
     * 刪除媒體檔案
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMedia(Request $request)
    {
        $request->validate([
            'competition_id' => 'required|exists:competitions,id',
            'type' => 'required|in:banner,attachment,athlete_card',
            'media_id' => 'required_if:type,attachment,athlete_card'
        ]);

        $competition = Competition::findOrFail($request->competition_id);
        
        // 檢查權限
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->back()
                ->with('error', '無權限刪除媒體檔案');
        }

        if ($request->type == 'banner') {
            $competition->clearMediaCollection('competitionBanner');
        } elseif ($request->type == 'attachment') {
            $competition->deleteMedia($request->media_id);
        } elseif ($request->type == 'athlete_card') {
            $competition->deleteMedia($request->media_id);
        }

        return redirect()->back()
            ->with('success', '媒體檔案刪除成功');
    }
}