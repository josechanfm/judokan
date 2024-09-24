<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompetitionApplication extends Model
{
    use HasFactory;
    protected $fillable = ['competition_id', 'organization_id', 'member_id', 'name_zh', 'name_fn', 'given_name', 'family_name', 'middle_name', 'display_name', 'id_num', 'gender', 'dob', 'belt_rank', 'email', 'mobile', 'category', 'weight', 'role', 'avatar', 'staff_options', 'referee_options', 'accepted', 'result_rank', 'result_score'];
    protected $casts = ['staff_options' => 'json', 'referee_options' => 'json', 'accepted' => 'boolean'];
    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : '';
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class)->with('media');
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public static function unique($competition, $data)
    {
        if ($data['role'] == 'athlete') {
            if ($competition->for_member) {
                $applicationCount = self::whereBelongsTo($competition)
                    ->where('role', 'athlete')
                    ->where('member_id', $data['member_id'])
                    ->where('weight', $data['weight'])
                    ->count();
                if ($applicationCount > 0) {
                    return false;
                }
                $applicationCount = self::whereBelongsTo($competition)
                    ->where('role', 'athlete')
                    ->where('member_id', $data['member_id'])
                    ->count();
                if ($applicationCount > 1) {
                    return false;
                }
            } else {
                $applicationCount = self::whereBelongsTo($competition)
                    ->where('role', 'athlete')
                    ->where('id_num', $data['id_num'])
                    ->where('weight', $data['weight'])
                    ->count();
                if ($applicationCount > 0) {
                    return false;
                };
                $applicationCount = self::whereBelongsTo($competition)
                    ->where('role', 'athlete')
                    ->where('id_num', $data['id_num'])
                    ->count();
                if ($applicationCount > 1) {
                    return false;
                };
            }
        } else {
            $applicationCount = self::whereBelongsTo($competition)
                ->where('id_num', $data['id_num'])
                ->where('role', '!=', 'athlete')
                ->count();
            if ($applicationCount > 1) {
                return false;
            }
        }
        return true;
    }
}
