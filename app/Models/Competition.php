<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Competition extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = ['organization_id', 'competition_score_id', 'title_zh', 'title_fn', 'brief', 'description', 'start_date', 'end_date', 'match_dates', 'categories_weights', 'roles', 'scope', 'for_member', 'staff_options', 'referee_options', 'fee', 'published'];
    protected $casts = ['match_dates' => 'json', 'categories_weights' => 'json', 'roles' => 'json', 'staff_options' => 'array', 'referee_options' => 'array', 'result_scores' => 'json'];

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Manipulations::FIT_CROP, 300, 300)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('competitionBanner')->useDisk('competition');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function applications()
    {
        return $this->hasMany(CompetitionApplication::class);
    }

    public function score()
    {
        return $this->belongsTo(CompetitionScore::class, 'competition_score_id', 'id');
    }
}
