<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrainingCamp extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'title', 'camp_date', 'points', 'remark'];

    protected $casts = ['camp_date' => 'date'];

    public function attendances()
    {
        return $this->hasMany(TrainingCampAttendance::class);
    }

    /**
     * 出席的會員。
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'training_camp_attendances')->withTimestamps();
    }
}
