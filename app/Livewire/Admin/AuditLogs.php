<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('سجل التدقيق - WINNER GYM')]
class AuditLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $userId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->role === 'owner' || $user?->hasGymPermission('audit.financial'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'userId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'userId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render(): View
    {
        $q = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.name as user_name', 'users.username as user_username')
            ->orderByDesc('audit_logs.id');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $q->where(fn ($x) => $x->where('audit_logs.category', 'ilike', $term)->orWhere('audit_logs.action', 'ilike', $term)->orWhere('users.name', 'ilike', $term)->orWhere('users.username', 'ilike', $term));
        }
        if ($this->category !== '') {
            $q->where('audit_logs.category', $this->category);
        }
        if ($this->userId !== '') {
            $q->where('audit_logs.user_id', $this->userId);
        }
        if ($this->dateFrom !== '') {
            $q->whereDate('audit_logs.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== '') {
            $q->whereDate('audit_logs.created_at', '<=', $this->dateTo);
        }

        return view('livewire.admin.audit-logs', [
            'logs' => $q->paginate(20),
            'users' => DB::table('users')->orderBy('name')->get(['id', 'name', 'username']),
            'categories' => DB::table('audit_logs')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'stats' => [
                'today' => DB::table('audit_logs')->whereDate('created_at', today())->count(),
                'security' => DB::table('audit_logs')->where('category', 'security')->count(),
                'financial' => DB::table('audit_logs')->whereIn('category', ['finance', 'financial'])->count(),
                'administration' => DB::table('audit_logs')->where('category', 'administration')->count(),
            ],
        ]);
    }
}
