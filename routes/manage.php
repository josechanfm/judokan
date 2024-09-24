<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Inertia\Inertia;


Route::group(['middleware' => config('fortify.middleware', ['admin_web'])], function () {
    $limiter = config('fortify.limiters.login');
    Route::get('/manage/login', function () {
        return Inertia::render('Organization/Login');
    })->middleware(['guest:' . config('fortify.guard')])->name('manage.login');

    Route::post('/manage/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'guest:' . config('fortify.guard'),
            $limiter ? 'throttle:' . $limiter : null,
        ]));
    Route::get('/manage/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('manage.logout');
});


// Route::middleware([
//     'auth:admin_web',
// ])->group(function () {
//     Route::get('/manage', function () {
//         // 如果是 admin 或者 master 就跳到 amdin
//         if (request()->user()->hasRole(['admin', 'master'])) return redirect()->to('/manage/admin');

//         // 否則去teacher
//         else return redirect()->to('/manage/teacher');
//     });
// });

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::prefix('/manage')->group(function () {
        Route::get('/', [App\Http\Controllers\Organization\DashboardController::class, 'index'])->name('manage.list');
        Route::get('/select/{organization}', [App\Http\Controllers\Organization\DashboardController::class, 'select'])->name('manage.select');
        Route::get('/dashboard', [App\Http\Controllers\Organization\DashboardController::class, 'index'])->name('manage.dashboard');
        Route::resource('members', App\Http\Controllers\Organization\MemberController::class)->names('manage.members');
        Route::resource('forms', App\Http\Controllers\Organization\FormController::class)->names('manage.forms');
        Route::resource('form/{form}/fields', App\Http\Controllers\Organization\FormFieldController::class)->names('manage.form.fields');

        //Route::resource('certificates', App\Http\Controllers\Organization\CertificateController::class)->names('manage.certificates');

        //Route::resource('organizations', App\Http\Controllers\Organization\OrganizationController::class)->names('manage.organizations');

        // Route::resource('organization.members', App\Http\Controllers\Organization\MemberController::class);
        // Route::resource('organization.certificates', App\Http\Controllers\Organization\CertificateController::class);
        // Route::get('organization/{organization}/certificate/{certificate}/members', [App\Http\Controllers\Organization\CertificateController::class,'members'])->name('organization.certificate.memebers');
        // Route::get('organization/{organization}/members/{member}/create_login', [App\Http\Controllers\Organization\MemberController::class,'createLogin']);
        // Route::resource('organization.forms',App\Http\Controllers\Organization\FormController::class);
        // Route::resource('organization.form.fields',App\Http\Controllers\Organization\FormFieldController::class);

        //Route::resource('members', App\Http\Controllers\Organization\MemberController::class);
        // Route::prefix('/member')->group(function(){

        // })->name('manage.organization.member');
    });
});

// Route::middleware([
//     'auth:admin_web',
//     config('jetstream.auth_session'),
//     'role:master',
// ])->group(function () {
//         Route::prefix('/master')->group(function(){
//             Route::get('/',[App\Http\Controllers\Admin\DashboardController::class,'index'])->name('master.dashboard');
//             Route::prefix('/member')->group(function(){
//                 Route::resource('/',App\Http\Controllers\Admin\MemberController::class);
//                 Route::get('/create_login/{member}',[App\Http\Controllers\Admin\MemberController::class,'createLogin']);
//             })->name('manage.mastermember');        
//         })->name('manage.master');
//         // Route::get('/',[App\Http\Controllers\Organization\DashboardController::class,'index']);
//         // Route::resource('organizations', App\Http\Controllers\Organization\OrganizationController::class);
//         // Route::resource('organization.members', App\Http\Controllers\Organization\MemberController::class);
//         // Route::prefix('/member')->group(function(){
            
//         // })->name('manage.organization.member');
// });


// Route::middleware([
//     'auth:admin_web',
//     'role:teacher',
// ])->group(function () {
//     Route::prefix('/manage/teacher')->group(function(){

//         Route::get('/',[App\Http\Controllers\Teacher\DashboardController::class,'index'])->name('teacher.dashboard');
    
//     })->name('teacher');
// });
