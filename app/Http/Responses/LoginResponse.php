<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Models\Member;
use Inertia\Inertia;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        // 屬會管理員：登入後直接去管理後台（即使同時是會員）
        if ($user->hasRole('organizer') && $user->organizations->count() > 0) {
            session(['organization' => $user->organizations->first()]);

            return redirect()->route('manage');
        }

        $member = $user->member;

        if ($member) {
            if ($member->organizations->count() > 0) {
                session(['organization' => $member->organizations->first()]);

                return redirect()->route('member.dashboard');
            }

            return Inertia::render('Error', [
                'message' => "You don't belongs to any organization",
            ]);
        }

        if ($user->organizations->count() > 0) {
            session(['organization' => $user->organizations->first()]);

            return redirect()->route('manage');
        }

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}