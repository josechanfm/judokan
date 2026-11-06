<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 屬會的運動員設定。
 *
 * is_club_athlete = 本館運動員
 * is_team_member  = 集訓隊成員（集訓記錄只會顯示這些人）
 */
class AthleteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'member_id',
        'is_club_athlete',
        'is_team_member',
    ];

    protected $casts = [
        'is_club_athlete' => 'boolean',
        'is_team_member' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 集訓隊成員的會員 id。
     */
    public static function teamMemberIds(int $organizationId): array
    {
        return self::where('organization_id', $organizationId)
            ->where('is_team_member', true)
            ->pluck('member_id')
            ->all();
    }

    /**
     * 本屬會所有運動員的會員 id（集訓隊成員 或 本館運動員）。
     */
    public static function athleteIds(int $organizationId): array
    {
        return self::where('organization_id', $organizationId)
            ->where(function ($query) {
                $query->where('is_team_member', true)
                    ->orWhere('is_club_athlete', true);
            })
            ->pluck('member_id')
            ->unique()
            ->values()
            ->all();
    }
}
