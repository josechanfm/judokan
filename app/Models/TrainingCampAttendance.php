<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingCampAttendance extends Model
{
    use HasFactory;

    protected $fillable = ['training_camp_id', 'member_id'];

    public function trainingCamp()
    {
        return $this->belongsTo(TrainingCamp::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
