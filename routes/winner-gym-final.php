<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\MemberInquiryController;
use App\Http\Controllers\ReportPdfController;
use App\Livewire\Admin\AuditLogs;
use App\Livewire\Admin\BranchesIndex;
use App\Livewire\Admin\ControlCenter;
use App\Livewire\Admin\GymSettings;
use App\Livewire\Admin\PeriodsIndex;
use App\Livewire\Admin\PermissionsIndex;
use App\Livewire\Backups\Index as BackupsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\WhatsApp\Index as WhatsAppIndex;
use Illuminate\Support\Facades\Route;

Route::get('/member-inquiry', [MemberInquiryController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('member.inquiry');

Route::post('/member-inquiry', [MemberInquiryController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('member.inquiry.lookup');

Route::middleware('auth')->group(function () {
    Route::livewire('/gym-dashboard', DashboardIndex::class)->name('gym.dashboard');
    Route::livewire('/reports', ReportsIndex::class)->middleware('gym.any:reports.operational,reports.finance')->name('reports.index');
    Route::get('/reports/pdf', ReportPdfController::class)->middleware('gym.any:reports.operational,reports.finance')->name('reports.pdf');

    Route::livewire('/admin', ControlCenter::class)->middleware('gym.any:staff.view,staff.manage,branches.manage,periods.manage,whatsapp.manage,backups.manage')->name('admin.index');
    Route::livewire('/admin/permissions', PermissionsIndex::class)->middleware('gym.owner')->name('admin.permissions');
    Route::livewire('/admin/branches', BranchesIndex::class)->middleware('gym.any:branches.manage')->name('admin.branches');
    Route::livewire('/admin/periods', PeriodsIndex::class)->middleware('gym.any:periods.manage')->name('admin.periods');

    Route::livewire('/gym-settings', GymSettings::class)->middleware('gym.owner')->name('gym.settings');
    Route::livewire('/audit-logs', AuditLogs::class)->middleware('gym.any:audit.financial')->name('audit.index');
    Route::livewire('/whatsapp', WhatsAppIndex::class)->middleware('gym.any:whatsapp.manage')->name('whatsapp.index');
    Route::livewire('/backups', BackupsIndex::class)->middleware('gym.any:backups.manage')->name('backups.index');
    Route::get('/backups/{backup}/download', BackupDownloadController::class)->middleware('gym.any:backups.manage')->name('backups.download');
});
