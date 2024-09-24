<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Competition;
use App\Models\CompetitionScore;
use App\Models\Config;

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
            'competitions' => Competition::where('organization_id', session('organization')->id)->paginate()
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
        $data = $request->all();
        // dd($data);
        $data['organization_id'] = session('organization')->id;
        $competition = Competition::create([...$data, 'staff_options' => $data['staffOptionsSelected'], 'referee_options' => $data['refereeOptionsSelected']]);
        $competition->save();

        if ($request->file('banner')) {
            foreach ($request->file('banner') as $file) {
                $competition->addMedia($file['originFileObj'])->toMediaCollection('competitionBanner');
            }
        };
        if ($request->file('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $competition->addMedia($file['originFileObj'])->toMediaCollection('competitionAttachment');
            }
        };
        return redirect()->route('manage.competitions.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Competition $competition)
    {
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index');
        }
        $competition->getMedia();
        return Inertia::render('Organization/CompetitionShow', [
            'competition' => $competition,
            'categories_weights' => Config::items('categories_weights', 0),
            'roles' => Config::item('competition_roles')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Competition $competition)
    {
        if ($competition->organization_id != session('organization')->id) {
            return redirect()->route('manage.competitions.index');
        }
        $competition->getMedia();
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Competition $competition)
    {
        $competition->update($request->all());
        $competition->result_scores = $competition->score ? $competition->score->toArray() : null;
        $competition->save();
        if ($request->file('banner')) {
            foreach ($request->file('banner') as $file) {
                $competition->addMedia($file['originFileObj'])->toMediaCollection('competitionBanner');
            }
        };
        if ($request->file('attachment')) {
            foreach ($request->file('attachment') as $file) {
                $competition->addMedia($file['originFileObj'])->toMediaCollection('competitionAttachment');
            }
        };
        //dd(json_encode($competition->score));
        //$competition->update(['result_scores'=>json_encode($competition->score)]);
        //return redirect()->back();
        return redirect()->route('manage.competitions.index');
        //return response($request->all());
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

    public function deleteMedia(Request $request)
    {
        if ($request->type == 'banner') {
            Competition::find($request->competition_id)->clearMediaCollection('competitionBanner');
        } elseif ($request->type == 'attachment') {
            //dd(Competition::find($request->competition_id)->getMedia('competitionAttachment'));
            Competition::find($request->competition_id)->deleteMedia($request->media_id);
        }
        return redirect()->back();
    }
}
