<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\AthleteSetting;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * 運動員設定：標記哪些會員是本館運動員、哪些是集訓隊成員。
 */
class AthleteSettingController extends Controller
{
    public function index()
    {
        $organizationId = session('organization')->id;
        $settings = AthleteSetting::where('organization_id', $organizationId)
            ->get()
            ->keyBy('member_id');

        $rows = Organization::find($organizationId)
            ->members
            ->map(function ($member) use ($settings) {
                $setting = $settings->get($member->id);

                return [
                    'member_id' => $member->id,
                    'name' => $member->display_name ?: ($member->name_zh ?: $member->name_fn),
                    'member_number' => $member->member_number,
                    'is_club_athlete' => (bool) ($setting->is_club_athlete ?? false),
                    'is_team_member' => (bool) ($setting->is_team_member ?? false),
                ];
            })
            ->sortBy(fn ($row) => mb_strtolower((string) $row['name']))
            ->values()
            ->all();

        return Inertia::render('Organization/AthleteSettings', [
            'rows' => $rows,
            'counts' => [
                'club' => count(array_filter($rows, fn ($row) => $row['is_club_athlete'])),
                'team' => count(array_filter($rows, fn ($row) => $row['is_team_member'])),
            ],
        ]);
    }

    /**
     * 一次儲存整張表（兩個勾選都沒有時刪除設定）。
     */
    public function save(Request $request)
    {
        $organizationId = session('organization')->id;

        $validated = $request->validate([
            'rows' => 'array',
            'rows.*.member_id' => 'required|integer',
            'rows.*.is_club_athlete' => 'boolean',
            'rows.*.is_team_member' => 'boolean',
        ]);

        $allowed = array_flip(
            Organization::find($organizationId)->members()->pluck('members.id')->all()
        );

        foreach ($validated['rows'] ?? [] as $row) {
            $memberId = (int) $row['member_id'];

            if (! isset($allowed[$memberId])) {
                continue;
            }

            $isClubAthlete = (bool) ($row['is_club_athlete'] ?? false);
            $isTeamMember = (bool) ($row['is_team_member'] ?? false);

            if (! $isClubAthlete && ! $isTeamMember) {
                AthleteSetting::where('organization_id', $organizationId)
                    ->where('member_id', $memberId)
                    ->delete();

                continue;
            }

            AthleteSetting::updateOrCreate(
                ['organization_id' => $organizationId, 'member_id' => $memberId],
                ['is_club_athlete' => $isClubAthlete, 'is_team_member' => $isTeamMember]
            );
        }

        return redirect()->back();
    }
}
