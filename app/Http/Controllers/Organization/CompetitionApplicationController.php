<?php

namespace App\Http\Controllers\Organization;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Config;
use App\Models\Competition;
use App\Models\CompetitionApplication;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use PDF;
use App\Exports\CompetitionApplicationExport;
use App\Imports\CompetitionApplicationImport;
use App\Mail\TestMail;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Competition $competition)
    {
        $competition->setRelation(
            'applications',
            $competition->applications()
                ->orderBy('category')
                ->orderBy('weight')
                ->orderBy('id')
                ->get()
        );
        $competition->score;
        // competitions 的 JSON 欄位可能是雙重編碼，一律正規化
        $competition->roles = $this->decodeJson($competition->roles);
        $competition->categories_weights = $this->decodeJson($competition->categories_weights);
        Session::put('competitionId', $competition->id);

        // 可加入報名的會員（本屬會）
        $organization = session('organization');
        $members = $organization
            ? $organization->members()
                ->select([
                    'members.id',
                    'members.name_zh',
                    'members.name_fn',
                    'members.display_name',
                    'members.gender',
                    'members.dob',
                    'members.email',
                    'members.mobile',
                    'members.avatar',
                ])
                ->orderBy('members.name_zh')
                ->get()
                ->map(fn ($member) => [
                    'id' => $member->id,
                    'name_zh' => $member->name_zh,
                    'name_fn' => $member->name_fn,
                    'display_name' => $member->display_name,
                    'gender' => $member->gender,
                    'dob' => $member->dob,
                    'email' => $member->email,
                    'mobile' => $member->mobile,
                    'avatar_url' => $member->avatar ? Storage::url($member->avatar) : '',
                    'member_number' => $member->member_number,
                ])
            : collect();

        return Inertia::render('Organization/CompetitionApplications', [
            'competitionResults' => Config::item('competition_results'),
            'organizations' => Organization::all(),
            'members' => $members,
            'competition' => $competition
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
        $organization = session('organization');

        if (! $organization) {
            return redirect()->back()->withErrors(['organization' => '找不到所屬屬會']);
        }

        $data = $request->validate([
            'member_id' => 'nullable|integer',
            'name_zh' => 'nullable|string|max:191',
            'name_fn' => 'nullable|string|max:191',
            'display_name' => 'nullable|string|max:191',
            'id_num' => 'nullable|string|max:50',
            'gender' => 'nullable|in:M,F,X',
            'dob' => 'nullable|date',
            'email' => 'nullable|email|max:191',
            'mobile' => 'nullable|string|max:50',
            'role' => 'required|string|max:50',
            'category' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'accepted' => 'nullable|boolean',
        ]);

        if (blank($data['name_zh'] ?? null) && blank($data['name_fn'] ?? null)) {
            return redirect()->back()->withErrors(['name_zh' => '請輸入姓名']);
        }

        if (
            ($data['role'] ?? null) === 'athlete' &&
            (blank($data['category'] ?? null) || blank($data['weight'] ?? null))
        ) {
            return redirect()->back()->withErrors(['category' => '運動員必須選擇組別與量級']);
        }

        // 重複報名檢查：同一人、同一角色、同一組別量級只能一筆
        $duplicate = CompetitionApplication::where('competition_id', $competition->id)
            ->where('role', $data['role'])
            ->when($data['role'] === 'athlete', function ($query) use ($data) {
                $query->where('category', $data['category'])->where('weight', $data['weight']);
            })
            ->when(! empty($data['member_id']), function ($query) use ($data) {
                $query->where('member_id', $data['member_id']);
            }, function ($query) use ($data) {
                if (! empty($data['id_num'])) {
                    $query->where('id_num', $data['id_num']);
                } else {
                    $query->where('name_zh', $data['name_zh'] ?? null)
                        ->where('name_fn', $data['name_fn'] ?? null);
                }
            })
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withErrors([
                'name_zh' => '這位選手已經報名了相同的組別與量級',
            ]);
        }

        $application = CompetitionApplication::create([
            'competition_id' => $competition->id,
            'organization_id' => $organization->id,
            'member_id' => $data['member_id'] ?? null,
            'name_zh' => $data['name_zh'] ?? null,
            'name_fn' => $data['name_fn'] ?? null,
            'display_name' => $data['display_name']
                ?: (($data['name_zh'] ?? null) ?: ($data['name_fn'] ?? null)),
            'id_num' => $data['id_num'] ?? null,
            'gender' => $data['gender'] ?? null,
            'dob' => ! empty($data['dob']) ? $data['dob'] : null,
            'email' => $data['email'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'role' => $data['role'],
            'category' => $data['category'] ?? null,
            'weight' => $data['weight'] ?? null,
            'accepted' => $request->boolean('accepted'),
        ]);

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
    public function update(Request $request, Competition $competition, $id)
    {
        $competitionApplication = CompetitionApplication::find($id);
        $data = $request->all();

        // 成績與積分：名次改變或清空時，積分要同步重算
        $scores = $competition->score?->toArray();
        if (!$scores) {
            $resultScores = $competition->result_scores;
            if (is_string($resultScores)) {
                $resultScores = json_decode(json_decode($resultScores, true), true);
            }
            $scores = is_array($resultScores) ? $resultScores : [];
        }
        $rank = array_key_exists('result_rank', $data)
            ? $data['result_rank']
            : $competitionApplication->result_rank;
        $data['result_rank'] = $rank ?: null;
        $data['result_score'] = $rank ? ($scores[$rank] ?? null) : null;

        $competitionApplication->update($data);
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Competition $competition, $id)
    {
        $competitionApplication = CompetitionApplication::find($id);
        if ($competitionApplication->avatar) {
            Storage::delete($competitionApplication->avatar);
        }

        $competitionApplication->delete();

        return redirect()->back();
    }

    public function receipts(Competition $competition, Request $request)
    {

        if (!session('competitionId') || session('competitionId') != $competition->id || $request->applicationIds==null) {
            return redirect()->route('/');
        }
        
        $applications = CompetitionApplication::whereIn('id', explode(',', $request->applicationIds))->get();
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->set_option("enable_php", true);
        // return view('Competition.ApplicationReceipt',[
        //     'competition'=>$competition,
        //     'applications'=>$applications
        // ]);
        $pdf = PDF::loadView('Competition.ApplicationReceipt', [
            'competition'=>$competition,
            'applications'=>$applications
        ]);
        $pdf->render();
        return $pdf->stream('receipts.pdf', array('Attachment' => false));
    }
    public function export(Competition $competition)
    {

        //echo $applications;
        //dd('aa');
        //dd($competition);
        // $baser64String = 'iVBORw0KGgoAAAANSUhEUgAAAJ4AAACeCAYAAADDhbN7AAAAAXNSR0IArs4c6QAAAsxJREFUeF7t0kEJACAABEGNaVizKRjC/cwVOFhmnr3OMAU+F5jgfS7u7hUAD4SkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOwWMgKQBekt0peAwkBcBLsjsFj4GkAHhJdqfgMZAUAC/J7hQ8BpIC4CXZnYLHQFIAvCS7U/AYSAqAl2R3Ch4DSQHwkuxOL7pc5wZFewbOAAAAAElFTkSuQmCC';
        // $image = base64_decode($baser64String);
        // dd($image);
        // $path = Storage::disk('public')->put('images/competitions/test.png', $image);
        // $data['avatar'] = $path;
        // dd($path);
        $data = new CompetitionApplicationExport($competition);
        return Excel::download($data, 'applications.xlsx');
    }
    public function import(Request $request, Competition $competition)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new CompetitionApplicationImport($competition);

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }
    public function success(CompetitionApplication $competitionApplication)
    {
        $application = CompetitionApplication::with('competition')->find($competitionApplication->id);
        
        if ($application->competition->categories_weights) {
            $application->competition->categories_weights = json_decode($application->competition->categories_weights, true);
        }
        
        if ($application->competition->roles) {
            $application->competition->roles = json_decode($application->competition->roles, true);
        }
        
        if ($application->competition->referee_options) {
            $application->competition->referee_options = json_decode($application->competition->referee_options, true);
        }
        
        if ($application->competition->staff_options) {
            $application->competition->staff_options = json_decode($application->competition->staff_options, true);
        }

        Session::flash('competitionApplication', $competitionApplication->id);
        return Inertia::render('Competition/Success', [
            'organizations' => Organization::all(),
            'belt_ranks' => Config::item("belt_ranks"),
            'application' => $application
        ]);
    }

    public function sendApplicationEmail(CompetitionApplication $competitionApplication)
    {
        $application = CompetitionApplication::with('competition')->find($competitionApplication->id);
        
        if ($application->competition->categories_weights) {
            $application->competition->categories_weights = json_decode($application->competition->categories_weights, true);
        }
        
        if ($application->competition->roles) {
            $application->competition->roles = json_decode($application->competition->roles, true);
        }
        
        if ($application->competition->referee_options) {
            $application->competition->referee_options = json_decode($application->competition->referee_options, true);
        }
        
        if ($application->competition->staff_options) {
            $application->competition->staff_options = json_decode($application->competition->staff_options, true);
        }
        
        $pdf = PDF::loadView('Competition.ApplicationSuccess', [
            'organizations' => Organization::all()->toArray(),
            'belt_ranks' => Config::item("belt_ranks"),
            'application' => $application,
        ]);
        $path = 'public/pdf/applications/' . $application->competition->title_zh . $application->name_fn . '.pdf';
        Storage::put($path, $pdf->output());
        $mailData = [
            'title' => '恭喜你已成功報名',
            'subject' => '比賽報名表',
            'view_name' => 'mail.applicationMail',
            'file_path' => $path,
        ];

        try {
            $email = CompetitionApplication::with('competition')->find($competitionApplication->id)->email;
            
            if ($email) {
                Mail::to($email)->send(new TestMail($mailData));
                \Log::info('郵件發送成功', ['email' => $email, 'application_id' => $competitionApplication->id]);
            } else {
                \Log::warning('郵件地址為空', ['application_id' => $competitionApplication->id]);
            }
            
        } catch (\Exception $e) {
            \Log::error('郵件發送失敗', [
                'error' => $e->getMessage(),
                'email' => $email ?? '未獲取到郵件地址',
                'application_id' => $competitionApplication->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return redirect()->back();
    }
}
