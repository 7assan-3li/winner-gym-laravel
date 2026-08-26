<?php

use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Packages\Index as PackagesIndex;
use App\Livewire\Staff\Index as StaffIndex;
use App\Livewire\Subscriptions\Index as SubscriptionsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/staff', StaffIndex::class)
        ->middleware('gym.any:staff.view,staff.manage')
        ->name('staff.index');

    Route::get('/members', MembersIndex::class)
        ->middleware('gym.any:members.view,members.manage')
        ->name('members.index');

    Route::get('/packages', PackagesIndex::class)
        ->middleware('gym.owner')
        ->name('packages.index');

    Route::get('/subscriptions', SubscriptionsIndex::class)
        ->middleware('gym.any:subscriptions.view,subscriptions.manage,subscriptions.create')
        ->name('subscriptions.index');

    Route::get('/attendance', AttendanceIndex::class)
        ->middleware('gym.any:attendance.view,attendance.record')
        ->name('attendance.index');
});
