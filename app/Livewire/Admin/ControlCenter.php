<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Models\GymPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الإدارة - WINNER GYM')]
class ControlCenter extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function render(): View
    {
        $latestBackup = Schema::hasTable('backup_logs')
            ? DB::table('backup_logs')->orderByDesc('id')->first()
            : null;

        $recentAudit = Schema::hasTable('audit_logs')
            ? DB::table('audit_logs')
                ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
                ->select('audit_logs.*', 'users.name as user_name')
                ->orderByDesc('audit_logs.id')->limit(5)->get()
            : collect();

        $whatsapp = Schema::hasTable('whatsapp_rules')
            ? [
                'rules' => DB::table('whatsapp_rules')->count(),
                'active' => DB::table('whatsapp_rules')->where('is_enabled', true)->count(),
                'sent' => Schema::hasTable('whatsapp_messages') ? DB::table('whatsapp_messages')->where('status', 'sent')->count() : 0,
            ]
            : ['rules' => 0, 'active' => 0, 'sent' => 0];

        return view('livewire.admin.control-center', [
            'stats' => [
                'employees' => User::count(),
                'active_employees' => User::where('is_active', true)->count(),
                'branches' => Branch::where('is_active', true)->count(),
                'periods' => GymPeriod::where('is_active', true)->count(),
                'permissions' => Schema::hasTable('role_permissions') ? DB::table('role_permissions')->where('allowed', true)->count() : 0,
                'audit_today' => Schema::hasTable('audit_logs') ? DB::table('audit_logs')->whereDate('created_at', today())->count() : 0,
            ],
            'branches' => Branch::withCount(['users', 'periods'])->orderByDesc('is_main')->orderBy('name')->limit(4)->get(),
            'periods' => GymPeriod::with('branch')->orderBy('branch_id')->orderBy('gender')->orderBy('slot_order')->get(),
            'latestBackup' => $latestBackup,
            'recentAudit' => $recentAudit,
            'whatsapp' => $whatsapp,
        ]);
    }
}
