<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateGymBackup extends Command
{
    protected $signature = 'winner-gym:backup';

    protected $description = 'Create a WINNER GYM application database backup';

    public function handle(BackupService $backups): int
    {
        $backup = $backups->create();
        $this->info("Backup created: {$backup->filename}");

        return self::SUCCESS;
    }
}
