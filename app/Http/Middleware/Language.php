<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class Language
{
    public function handle($request, Closure $next)
    {
        // 依序取用：session（使用者手動切換）→ 使用者偏好 → 應用預設（config/app.php: zh-TW）
        $locale = Session::get('applocale')
            ?? Auth::user()?->language
            ?? Config::get('app.locale');

        // 前端翻譯檔是 lang/zh-TW.json，PHP 端翻譯資料夾是 lang/zh_TW，這裡做對應
        App::setLocale($locale === 'zh-TW' ? 'zh_TW' : $locale);

        return $next($request);
    }
}
