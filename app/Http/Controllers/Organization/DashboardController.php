<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Article;
use App\Models\Certificate;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\Exam;
use App\Models\Event;
use App\Models\Message;
use App\Models\Organization;
use App;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Organization::class);
    }

    // public function list()
    // {
    //     $organizations=auth()->user()->organizations;
    //     if($organizations->count()==0){
    //         return redirect('/');
    //     }else if($organizations->count()==1){
    //         session(['organization'=>$organizations[0]]);
    //         return redirect()->route('manage.dashboard');
    //     }else{
    //         return Inertia::render('Organization/Selection',[
    //             'organizations' => $organizations
    //         ]);
    //     }
    // }

    public function select(Organization $organization){
        $this->authorize('view',$organization);
        session(['organization'=>$organization]);
        return redirect()->route('manage');
    }

    public function index(){
        $organization = session('organization');
        $this->authorize('view',$organization);

        $organizationId = $organization->id;

        $competitions = Competition::where('organization_id',$organizationId);

        $stats = [
            'members' => $organization->members()->count(),
            'forms' => $organization->forms()->count(),
            'competitions' => (clone $competitions)->count(),
            'published_competitions' => (clone $competitions)->where('published',true)->count(),
            'events' => Event::where('organization_id',$organizationId)->count(),
            'articles' => Article::where('organization_id',$organizationId)->count(),
            'certificates' => Certificate::where('organization_id',$organizationId)->count(),
            'messages' => Message::where('organization_id',$organizationId)->count(),
            'exams' => Exam::where('organization_id',$organizationId)->count(),
        ];

        $recentCompetitions = (clone $competitions)
            ->withCount('applications')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id','title_zh','title_fn','start_date','end_date','published']);

        $recentApplications = CompetitionApplication::where('organization_id',$organizationId)
            ->with('competition:id,title_zh,title_fn')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id','competition_id','name_zh','name_fn','display_name','role','accepted','created_at']);

        $recentMembers = $organization->members()
            ->orderByDesc('members.created_at')
            ->limit(5)
            ->get(['members.id','members.name_zh','members.name_fn','members.display_name','members.created_at','members.avatar']);

        return Inertia::render('Organization/Dashboard',[
            'organization'=>$organization,
            'organizations'=>auth()->user()->organizations,
            'member'=>auth()->user()->member,
            'stats'=>$stats,
            'recentCompetitions'=>$recentCompetitions,
            'recentApplications'=>$recentApplications,
            'recentMembers'=>$recentMembers,
        ]);
    }
    
}
