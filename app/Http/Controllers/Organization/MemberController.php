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
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
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
        $organizationId = (int) session('organization')->id;

        return Inertia::render('Organization/Member/Index',[
            //'members'=>session('organization')->members
            'memberTiers'=>Config::item('member_tiers'),
            'belt_ranks' => $belt_ranks,
            // 前端 Index.vue 的 breadcrumb / 導入會用 this.organization.id
            'organization' => session('organization'),
            'members'=>Organization::find($organizationId)->members,
            // 「管理員」分頁
            'admins' => $this->organizationAdmins($organizationId),
            'adminCandidates' => $this->adminCandidates($organizationId),
        ]);

    }

    /**
     * 本屬會的管理員（有 organizer 角色 + 屬於本屬會）。
     */
    private function organizationAdmins(int $organizationId): array
    {
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'organizer'))
            ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))
            ->orderBy('name')
            ->get();

        $members = Member::whereIn('user_id', $users->pluck('id')->all())
            ->get()
            ->keyBy('user_id');

        return $users->map(function (User $user) use ($members) {
            $member = $members->get($user->id);

            return [
                'id' => $member?->id,
                'user_id' => $user->id,
                'name' => $member?->display_name ?: ($member?->name_zh ?: $user->name),
                'email' => $user->email,
                'member_number' => $member?->member_number,
                'is_self' => $user->id === auth()->id(),
            ];
        })->values()->all();
    }

    /**
     * 可以授權為管理員的會員：本屬會會員、已建立登入帳號、未是管理員。
     */
    private function adminCandidates(int $organizationId): array
    {
        $memberIds = \DB::table('member_organization')
            ->where('organization_id', $organizationId)
            ->pluck('member_id')
            ->all();

        $adminUserIds = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'organizer'))
            ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $organizationId))
            ->pluck('id')
            ->all();

        if ($memberIds === []) {
            return [];
        }

        return Member::whereIn('id', $memberIds)
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', $adminUserIds)
            ->orderBy('name_zh')
            ->get()
            ->map(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->display_name ?: ($member->name_zh ?: $member->name_fn),
                'email' => $member->email,
            ])
            ->values()
            ->all();
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
     * 激活／續期會員：依選擇的月數延長會員期限。
     *
     * 未過期 → 由原到期日往後加；已過期或未設定 → 由今天起算。
     */
    public function activate(Request $request, Member $member)
    {
        $organization = session('organization');

        abort_unless(
            $member->organizations()->where('organizations.id', $organization->id)->exists(),
            403
        );

        $data = $request->validate([
            'months' => 'required|integer|in:1,3,6,12',
        ]);

        $today = Carbon::today();
        $currentExpiry = $member->expired_at ? Carbon::parse($member->expired_at) : null;
        $base = $currentExpiry && $currentExpiry->greaterThan($today) ? $currentExpiry : $today;

        $member->valid_at = $member->valid_at ?: $today->format('Y-m-d');
        $member->expired_at = $base->copy()->addMonths((int) $data['months'])->format('Y-m-d');
        $member->save();

        return redirect()->back();
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

    /**
     * 建立會員的登入帳號。
     *
     * mode = email：建立帳號後寄出「設定密碼」電郵（由會員自己設定）；
     * mode = password：由管理員直接設定密碼，帳號即時可用。
     * 會員已有帳號時，只可以重設密碼。
     */
    public function createLogin(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $data = $request->validate([
            'mode' => 'nullable|in:email,password',
            'password' => 'nullable|required_if:mode,password|string|min:8|confirmed',
        ]);

        $mode = $data['mode'] ?? 'email';

        if (empty($member->email)) {
            return response()->json(['result' => false, 'message' => '此會員沒有填寫電郵，無法建立登入帳號']);
        }

        if (! empty($member->user)) {
            if ($mode !== 'password') {
                return response()->json(['result' => false, 'message' => 'Login Account already created!']);
            }

            $user = $member->user;
            $user->password = Hash::make($data['password']);
            $user->email_verified_at = $user->email_verified_at ?: now();
            $user->save();

            return response()->json(['result' => true, 'message' => '密碼已更新，會員可以即刻用新密碼登入']);
        }

        if (User::where('email', $member->email)->exists()) {
            return response()->json(['result' => false, 'message' => 'Email already in used!']);
        }

        $user = $member->createUser($mode === 'password' ? $data['password'] : null);

        if ($mode === 'password') {
            return response()->json(['result' => true, 'message' => '登入帳號已建立，會員可以即刻用你設定的密碼登入']);
        }

        Password::broker(config('fortify.passwords'))->sendResetLink(
            ['email' => $user->email]
        );

        return response()->json(['result' => true, 'message' => 'Login account created, please check you email.']);
    }

    /**
     * 授予會員「本屬會管理員」權限。
     *
     * 需要再輸入一次自己的登入密碼確認。
     */
    public function storeAdmin(Request $request)
    {
        $organization = session('organization');

        $data = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'password' => 'required|string',
        ]);

        if (! Hash::check($data['password'], (string) $request->user()->password)) {
            return response()->json(['result' => false, 'message' => '密碼不正確，未能授權'], 422);
        }

        $member = Member::find($data['member_id']);

        abort_unless($member, 404);
        abort_unless(
            $member->organizations()->where('organizations.id', $organization->id)->exists(),
            403
        );

        if (empty($member->user_id)) {
            return response()->json(
                ['result' => false, 'message' => '此會員仍未建立登入帳號，請先建立帳號'],
                422
            );
        }

        $user = $member->user;
        $user->assignRole('organizer');

        if (! $user->organizations()->where('organizations.id', $organization->id)->exists()) {
            $user->organizations()->attach($organization->id);
        }

        return response()->json([
            'result' => true,
            'message' => ($member->display_name ?: $member->name_zh).' 已成為本屬會管理員',
        ]);
    }

    /**
     * 移除某個帳號的「本屬會管理員」權限。
     *
     * 需要再輸入一次自己的登入密碼確認；不能移除自己。
     */
    public function destroyAdmin(Request $request, User $user)
    {
        $organization = session('organization');

        $data = $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($data['password'], (string) $request->user()->password)) {
            return response()->json(['result' => false, 'message' => '密碼不正確，未能移除'], 422);
        }

        abort_unless(
            $user->organizations()->where('organizations.id', $organization->id)->exists(),
            403
        );

        if ($user->id === $request->user()->id) {
            return response()->json(['result' => false, 'message' => '不能移除自己的管理員權限'], 422);
        }

        $user->organizations()->detach($organization->id);

        // 不再屬於任何屬會就一併移除 organizer 角色
        if ($user->organizations()->count() === 0) {
            $user->removeRole('organizer');
        }

        return response()->json(['result' => true, 'message' => '已移除管理員權限']);
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
