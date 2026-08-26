<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Services\BackupService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(int $backup, BackupService $backups): BinaryFileResponse
    {
        abort_unless(auth()->user()?->role === 'owner', 403);

        $row = BackupLog::query()->findOrFail($backup);
        abort_unless($row->status === 'completed', 404);

        return response()->download(
            $backups->absolutePath($row),
            $row->filename ?: $row->file_name,
            ['Cache-Control' => 'no-store, private'],
        );
    }
}
