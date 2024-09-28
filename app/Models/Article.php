<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Article extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['uuid', 'organization_id', 'category_code', 'title_en', 'title_fn', 'content_en', 'content_fn', 'valid_at', 'expire_at', 'url', 'reference', 'published', 'public', 'author', 'user_id'];

    public static function publics()
    {
        return Article::where('published', true)->where('public', true)
            ->where(function ($query) {
                $query->whereNull('valid_at')->orWhere('valid_at', '<=', date('Y-m-d'));
            })
            ->where(function ($query) {
                $query->whereNull('expire_at')->orWhere('expire_at', '>=', date('Y-m-d'));
            })
            ->get();
    }
    public static function privates()
    {
        $member = auth()->user()->member;
        return Article::where('published', true)->where('public', false)->where('organization_id', session('organization') ? session('organization')->id : $member->organizations[0]->id)
            ->where(function ($query) {
                $query->whereNull('valid_at')->orWhere('valid_at', '<=', date('Y-m-d'));
            })
            ->where(function ($query) {
                $query->whereNull('expire_at')->orWhere('expire_at', '>=', date('Y-m-d'));
            })
            ->get();
    }
}
