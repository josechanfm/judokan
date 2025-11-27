<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Organization;
use App\Models\User;
use App\Models\Member;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->hasRole(['admin','master'])) {
            // 獲取統計數據
            $organizationsCount = Organization::count();
            $membersCount = Member::count();
            $usersCount = User::count();

            // 獲取組織列表
            $organizations = Organization::withCount('members')
                // ->select(['id', 'full_name', 'created_at'])
                ->latest()
                ->get();

            // dd($organizations);
            return Inertia::render('Admin/Dashboard', [
                'organizations' => $organizations,
                'stats' => [
                    'organizations_count' => $organizationsCount,
                    'members_count' => $membersCount,
                    'users_count' => $usersCount,
                ]
            ]);
        } else {
            return redirect('/');
        }
    }
}