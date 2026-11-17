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
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class CompetitionApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Competition $competition)
    {
        $sortField = $request->query('sort');
        $sortDirection = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $applications = $competition->applications();

        // 欄位排序（後端處理），未指定時預設「會員先 → 組別 → 量級 → 姓名」
        switch ($sortField) {
            case 'athlete':
                $applications->orderBy('name_zh', $sortDirection)
                    ->orderBy('name_fn', $sortDirection);
                break;
            case 'gender':
                $applications->orderBy('gender', $sortDirection);
                break;
            case 'organization':
                $applications->orderBy('organization_id', $sortDirection);
                break;
            case 'member':
                // member_id 有值＝會員，asc 時會員排前面
                $applications->orderByRaw("member_id IS NULL {$sortDirection}");
                break;
            case 'role':
                $applications->orderBy('role', $sortDirection);
                break;
            case 'category':
                $applications->orderBy('category', $sortDirection);
                break;
            case 'accepted':
                $applications->orderBy('accepted', $sortDirection);
                break;
            case 'competition_result':
                $applications->orderBy('result_rank', $sortDirection);
                break;
            default:
                $sortField = null;
                $applications->orderByRaw('member_id IS NULL asc')
                    ->orderBy('category')
                    ->orderBy('weight')
                    ->orderBy('name_zh');
                break;
        }

        if ($sortField) {
            // 同一排序值時保持組別／量級的穩定次序
            $applications->orderBy('category')->orderBy('weight')->orderBy('id');
        }

        $competition->setRelation('applications', $applications->get());
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
            'sort' => $sortField,
            'sortDirection' => $sortField ? $sortDirection : null,
            'competition' => $competition
        ]);
    }

    /**
     * 自動對應會員：把「未有會員」的報名，按電郵／手機／姓名+出生日期比對會員資料。
     * 只回傳比對結果，不寫入資料庫。
     */
    public function memberMatches(Competition $competition)
    {
        [$matches, $unmatched] = $this->collectMemberMatches($competition);

        return response()->json([
            'matches' => $matches,
            'unmatched' => $unmatched,
            'summary' => [
                'matched' => count($matches),
                'unmatched' => $unmatched,
            ],
        ]);
    }

    /**
     * 套用自動對應：按同樣規則把符合的報名綁定到會員。
     */
    public function bindMembers(Competition $competition)
    {
        [$matches] = $this->collectMemberMatches($competition);

        $bound = 0;
        foreach ($matches as $match) {
            $updated = CompetitionApplication::where('competition_id', $competition->id)
                ->where('id', $match['application_id'])
                ->whereNull('member_id')
                ->update(['member_id' => $match['member_id']]);

            $bound += $updated;
        }

        if (request()->expectsJson()) {
            return response()->json(['bound' => $bound, 'unmatched' => count($matches) - $bound]);
        }

        return redirect()->back();
    }

    /**
     * 逐一比對未有會員的報名與會員資料。
     *
     * @return array{0: array, 1: int} [比對結果, 找不到會員的報名數]
     */
    private function collectMemberMatches(Competition $competition): array
    {
        $organization = session('organization');
        $members = Member::with('organizations')->get();
        $categories = $this->decodeJson($competition->categories_weights) ?: [];

        $applications = CompetitionApplication::where('competition_id', $competition->id)
            ->whereNull('member_id')
            ->orderBy('category')
            ->orderBy('weight')
            ->orderBy('id')
            ->get();

        $matches = [];
        $unmatched = 0;

        foreach ($applications as $application) {
            $result = $this->findMemberFor($application, $members, $organization);

            if (! $result) {
                $unmatched++;
                continue;
            }

            [$member, $matchedBy] = $result;

            $matches[] = [
                'application_id' => $application->id,
                'name' => $application->display_name ?: ($application->name_zh ?: $application->name_fn),
                'id_num' => $application->id_num,
                'gender' => $application->gender,
                'category' => $this->categoryName($categories, $application->category),
                'weight' => $application->weight,
                'member_id' => $member->id,
                'member_name' => $member->name_zh ?: ($member->display_name ?: $member->name_fn),
                'member_number' => $member->member_number,
                'matched_by' => $matchedBy,
            ];
        }

        return [$matches, $unmatched];
    }

    /**
     * 依序以電郵 → 手機 → 姓名＋出生日期找出對應會員，
     * 同一組候選人優先取本屬會的會員；有多位可能時不自動對應。
     *
     * @return array{0: Member, 1: string}|null [會員, 比對依據]
     */
    private function findMemberFor(CompetitionApplication $application, $members, $organization): ?array
    {
        $email = strtolower(trim((string) $application->email));
        if ($email !== '') {
            $member = $this->pickMember(
                $members->filter(fn ($item) => strtolower(trim((string) $item->email)) === $email),
                $organization
            );
            if ($member) {
                return [$member, 'email'];
            }
        }

        $mobile = $this->normalizeMobile($application->mobile);
        if (strlen($mobile) >= 6) {
            $member = $this->pickMember(
                $members->filter(function ($item) use ($mobile) {
                    $memberMobile = $this->normalizeMobile($item->mobile);

                    // 手機可能帶國碼，只要尾碼相同就當作同一支號碼
                    return $memberMobile !== ''
                        && (str_ends_with($memberMobile, $mobile) || str_ends_with($mobile, $memberMobile));
                }),
                $organization
            );
            if ($member) {
                return [$member, 'mobile'];
            }
        }

        // 姓名比對必須同時有出生日期，避免同名同姓誤配
        $nameZh = trim((string) $application->name_zh);
        $dob = substr((string) $application->dob, 0, 10);
        if ($nameZh !== '' && $dob !== '') {
            $member = $this->pickMember(
                $members->filter(function ($item) use ($nameZh, $dob, $application) {
                    if (trim((string) $item->name_zh) !== $nameZh) {
                        return false;
                    }

                    if (substr((string) $item->dob, 0, 10) !== $dob) {
                        return false;
                    }

                    // 兩邊都有性別時要一致（舊資料可能是 0/1）
                    if (! $application->gender || ! $item->gender) {
                        return true;
                    }

                    return $this->normalizeGender($item->gender) === $application->gender;
                }),
                $organization
            );
            if ($member) {
                return [$member, 'name_dob'];
            }
        }

        return null;
    }

    /**
     * 會員性別舊資料可能是 0/1（0 = 男、1 = 女），統一轉成 M／F。
     */
    private function normalizeGender($value): ?string
    {
        return match ((string) $value) {
            'M', 'm', '0' => 'M',
            'F', 'f', '1' => 'F',
            default => null,
        };
    }

    /**
     * 從候選會員中選出唯一一位：先看本屬會，沒有的話要全體只有一位才採用。
     */
    private function pickMember($candidates, $organization): ?Member
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        if ($organization) {
            $inOrganization = $candidates->filter(
                fn ($member) => $member->organizations->contains('id', $organization->id)
            );

            if ($inOrganization->count() === 1) {
                return $inOrganization->first();
            }

            if ($inOrganization->count() > 1) {
                return null;
            }
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    private function normalizeMobile($value): string
    {
        return preg_replace('/\D/', '', (string) $value);
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
            'bound' => $import->boundCount(),
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
