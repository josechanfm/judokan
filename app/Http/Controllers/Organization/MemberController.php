<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Config;
use App\Models\Organization;
use App\Models\Member;
use App\Models\MemberTier;
use App\Models\User;
use App\Exports\MemberExport;
use App\Imports\MemberImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Password;

class MemberController extends Controller
{
    public function __construct()
    {
        //$this->authorizeResource(Organization::class);
        $this->authorizeResource(Member::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $belt_ranks = collect(Config::item('belt_ranks'));
        return Inertia::render('Organization/Member/Index',[
            //'members'=>session('organization')->members
            'memberTiers'=>Config::item('member_tiers'),
            'belt_ranks' => $belt_ranks,
            // 前端 Index.vue 的 breadcrumb / 導入會用 this.organization.id
            'organization' => session('organization'),
            'members'=>Organization::find(session('organization')->id)->members
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Organization/Member/CreateOrUpdate', [
            'memberTiers' => Config::item('member_tiers'),
            'organization' => session('organization'),
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
        $member = Member::create($request->all());
        $member->organizations()->attach(session('organization')->id);
        $this->syncTiers($member, $request->input('tiers', []));

        return redirect()->route('manage.members.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Member $member)
    {
        return Inertia::render('Organization/Member',[
            'memberTiers' => Config::item('member_tiers'),
            'member'=>$member->load('tiers'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Member $member)
    {
        return Inertia::render('Organization/Member/CreateOrUpdate', [
            'memberTiers' => Config::item('member_tiers'),
            'organization' => session('organization'),
            'member' => $member->load('tiers'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Member $member)
    {
        $member->update($request->all());
        $this->syncTiers($member, $request->input('tiers', []));

        return redirect()->route('manage.members.index');
    }

    /**
     * 依表單傳來的 tiers 陣列新增/更新會員級別（只處理屬於這位會員的資料）。
     */
    private function syncTiers(Member $member, array $tiers): void
    {
        foreach ($tiers as $tier) {
            $data = [
                'tier_code' => $tier['tier_code'] ?? null,
                'valid_at' => $tier['valid_at'] ?? null,
                'expired_at' => $tier['expired_at'] ?? null,
            ];

            if (! empty($tier['id'])) {
                MemberTier::where('id', $tier['id'])->where('member_id', $member->id)->update($data);
            } else {
                $member->tiers()->create($data);
            }
        }
    }

    /**
     * 刪除單一會員級別（CreateOrUpdate.vue 的級別列表使用）。
     */
    public function destroyTier(MemberTier $tier)
    {
        $member = Member::find($tier->member_id);
        abort_unless($member && $member->ownedBy(session('organization')), 403);

        $tier->delete();

        return response()->json(['result' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Member $member)
    {
        if($member->ownedBy(session('organization'))){
            $member->organizations()->detach();
            $member->delete();
        }
        return redirect()->back();
    }

    public function createLogin(Member $member){
        $this->authorize('update',$member);
        if (empty($member->user)) {
            $user=User::where('email',$member->email)->first();
            if($user){
                return response()->json(['result'=>false,'message'=>'Email already in used!']);
            }else{
                $user = $member->createUser();
            }
        } else {
            return response()->json(['result'=>false,'message'=>'Login Account already created!']);
            $user = $member->user;
        }
        Password::broker(config('fortify.passwords'))->sendResetLink(
            [ 'email' => $user->email ]
        );
        return response()->json(['result'=>true,'message'=>'Login account created, please check you email.']);
    }
    public function export(Organization $organization){
        return Excel::download(new MemberExport($organization), 'members.xlsx');
    }

    /**
     * 從 Excel 匯入會員（前端 Organization/Member/Index.vue 的「導入」）。
     * 回傳 { imported, errors }：errors 的每一筆都是 { row, attribute, errors }。
     */
    public function import(Organization $organization, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new MemberImport($organization);

        Excel::import($import, $request->file('file'));

        return response()->json([
            'imported' => $import->importedCount(),
            'errors' => $import->failures()->values(),
        ]);
    }
}
