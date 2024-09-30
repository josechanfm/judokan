<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Config;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use PDF;

class CompetitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $member = auth()?->user()?->member;
        // dd($member);
        $competitions = Competition::where('published', 1)->where(function ($query) use ($member) {
            $query->where('scope', 'PUB')->orWhere('scope', 'MJA')->orWhere(function ($query) use ($member) {
                $query->where('organization_id', session('organization') ? session('organization')->id : $member?->organizations[0]?->id);
            });
        })->get();
        return Inertia::render('Competition/Competitions', [
            'competitions' => $competitions
        ]);
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
    public function store(Request $request)
    {

        $data = $request->all();
        // dd($data);
        $competition = Competition::find($data['competition_id']);
        if ($competition->scope != 'PUB') { // for member only
            if ($data['role'] == 'athlete') { //member, role as athelte
                //max 2 applicatition same competition, in defferent category and/or weight
                $applicationCount = CompetitionApplication::where('competition_id', $data['competition_id'])
                    ->where('member_id', $data['member_id'])
                    ->count();
                if ($applicationCount >= 2) {
                    return redirect()->back()->withErrors(['message' => 'You have 2 applications']);
                }
                //max 1 in same category same weight
                $applicationCount = CompetitionApplication::where('competition_id', $data['competition_id'])
                    ->where('member_id', $data['member_id'])
                    ->where('category', $data['category'])
                    ->where('weight', $data['weight'])
                    ->count();
                if ($applicationCount >= 1) {
                    return redirect()->back()->withErrors(['message' => 'Duplicate in same category and/or weight']);
                }
                $applicationCount = CompetitionApplication::where('competition_id', $data['competition_id'])
                    ->where('member_id', $data['member_id'])
                    ->where('role', '!=', 'athlete')
                    ->count();
                if ($applicationCount >= 1) {
                    return redirect()->back()->withErrors(['message' => 'Applied multiple roles']);
                }
            } else { //member, role as not athelete
                //max 1 in same category same weight
                $applicationCount = CompetitionApplication::where('competition_id', $data['competition_id'])
                    ->where('id_num', $data['id_num'])
                    ->count();
                if ($applicationCount >= 1) {
                    return redirect()->back()->withErrors(['message' => 'Duplicate application']);
                }
            }
        } 

        if ($request->file('avatar')) {
            $file = $request->file('avatar');
            $path = Storage::putFile('public/images/competitions/avatar', $file);
            $data['avatar'] = $path;
        }
        if (!empty($data['member_id'])) {
            $member = Member::where('id', $data['member_id'])->first();
            $data['organization_id'] = session('organization') ? session('organization')->id : $member->organizations[0]->id;
        }
        $application = CompetitionApplication::create($data);

        //return redirect()->route('competitions.index');
        Session::flash('competitionApplication', $application->id);
        return redirect()->route('competition.application.success', $application->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Competition $competition)
    {
        $competition->getMedia();
        return Inertia::render('Competition/ApplicationForm', [
            'organizations' => Organization::all(),
            'belt_ranks' => Config::item("belt_ranks"),
            'competition' => $competition,
            'member' => auth()->user()?->member
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Session::flash('competitionApplication', $id);

        return redirect()->route('competition.application.success', $id);
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
        //
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
    public function applicationSuccess($id, Request $request)
    {
        //$application=CompetitionApplication::with('competition')->find($id);
        //dd($application);

        if (strtoupper($request->format) == 'PDF') {
            // if (!session('competitionApplicationPdf') || session('competitionApplicationPdf') != $id) {
            //     return redirect()->route('/');
            // }
            // return view('Competition/ApplicationSuccess',[
            //     'belt_ranks'=>Config::item("belt_ranks"),
            //     'application'=>CompetitionApplication::with('competition')->find($id)
            // ]);
            $pdf = PDF::loadView('Competition.ApplicationSuccess', [
                'organizations' => Organization::all()->toArray(),
                'belt_ranks' => Config::item("belt_ranks"),
                'application' => CompetitionApplication::with('competition')->find($id)
            ]);
            $pdf->render();
            return $pdf->stream('receipt.pdf', array('Attachment' => false));
        } else {
            if (!session('competitionApplication') || session('competitionApplication') != $id) {
                return redirect()->route('/');
            }
            Session::flash('competitionApplicationPdf', $id);
            return Inertia::render('Competition/Success', [
                'organizations' => Organization::all(),
                'belt_ranks' => Config::item("belt_ranks"),
                'application' => CompetitionApplication::with('competition')->find($id)
            ]);
        }
    }
}
