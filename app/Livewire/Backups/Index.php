<?php

namespace App\Livewire\Backups;

use App\Models\BackupLog;
use App\Services\AuditService;
use App\Services\BackupService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('النسخ الاحتياطي - WINNER GYM')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
    }

    public function create(BackupService $service, AuditService $audit): void
    {
        try {
            $backup = $service->create(auth()->user());
            $audit->log(auth()->user(), 'administration', 'backup.created', $backup, null, [
                'size_bytes' => $backup->size_bytes,
            ]);
            session()->flash('success', "تم إنشاء النسخة: {$backup->filename}");
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('backup', 'تعذر إنشاء النسخة الاحتياطية. راجع سجل النظام ومساحة التخزين.');
        }
    }

    public function delete(int $id, BackupService $service, AuditService $audit): void
    {
        $backup = BackupLog::query()->findOrFail($id);
        $service->delete($backup);
        $audit->log(auth()->user(), 'administration', 'backup.deleted', null, null, ['backup_id' => $id]);
        session()->flash('success', 'تم حذف النسخة الاحتياطية.');
    }

    public function render(): View
    {
        $backups = BackupLog::query()->latest('id')->limit(50)->get();

        return view('livewire.backups.index', [
            'backups' => $backups,
            'latest' => $backups->first(),
            'stats' => [
                'total' => $backups->count(),
                'completed' => $backups->where('status', 'completed')->count(),
                'failed' => $backups->where('status', 'failed')->count(),
                'size' => $backups->sum('size_bytes'),
            ],
        ]);
    }
}
