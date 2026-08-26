<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use FilesystemIterator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    private const TABLES = [
        'users',
        'role_permissions',
        'user_permissions',
        'settings',
        'branches',
        'gym_periods',
        'members',
        'packages',
        'subscriptions',
        'subscription_installments',
        'subscription_payments',
        'subscription_refunds',
        'attendances',
        'attendance_attempts',
        'expense_categories',
        'expenses',
        'product_categories',
        'products',
        'purchases',
        'purchase_items',
        'sales',
        'sale_items',
        'inventory_movements',
        'nutrition_clients',
        'nutritionist_schedules',
        'appointments',
        'appointment_payments',
        'measurement_types',
        'measurements',
        'measurement_values',
        'whatsapp_rules',
        'whatsapp_messages',
        'audit_logs',
    ];

    public function create(?User $actor = null): BackupLog
    {
        $startedAt = now(config('app.timezone'));
        $stamp = $startedAt->format('Ymd-His').'-'.bin2hex(random_bytes(3));
        $filename = "winner-gym-{$stamp}.zip";
        $relativePath = "backups/{$filename}";
        $temporaryDirectory = storage_path("app/private/backups/tmp-{$stamp}");
        $databaseFile = $temporaryDirectory.'/database.json';
        $archivePath = storage_path("app/private/{$relativePath}");

        $backup = BackupLog::create([
            'file_name' => $filename,
            'filename' => $filename,
            'storage_path' => $relativePath,
            'disk' => 'local',
            'path' => $relativePath,
            'status' => 'running',
            'started_at' => $startedAt,
            'initiated_by' => $actor?->id,
            'created_by' => $actor?->id,
        ]);

        try {
            $this->ensureDirectory($temporaryDirectory);
            $this->ensureDirectory(dirname($archivePath));
            $this->writeDatabaseJson($databaseFile);
            $this->createArchive($archivePath, $databaseFile);

            clearstatcache(true, $archivePath);
            $size = filesize($archivePath);
            if ($size === false || $size <= 0) {
                throw new RuntimeException('تم إنشاء ملف نسخة احتياطية فارغ.');
            }

            $backup->update([
                'size_bytes' => $size,
                'status' => 'completed',
                'completed_at' => now(config('app.timezone')),
                'error_message' => null,
            ]);

            $this->pruneExpired($backup->id);

            return $backup->fresh();
        } catch (Throwable $exception) {
            if (is_file($archivePath)) {
                @unlink($archivePath);
            }

            $backup->update([
                'status' => 'failed',
                'completed_at' => now(config('app.timezone')),
                'error_message' => mb_substr($exception->getMessage(), 0, 3000),
            ]);

            throw $exception;
        } finally {
            if (is_file($databaseFile)) {
                @unlink($databaseFile);
            }
            if (is_dir($temporaryDirectory)) {
                @rmdir($temporaryDirectory);
            }
        }
    }

    public function downloadUrl(BackupLog $backup): string
    {
        return route('backups.download', ['backup' => $backup->id]);
    }

    public function absolutePath(BackupLog $backup): string
    {
        $path = $this->resolveLocalBackupPath($backup);
        if (! is_file($path)) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير موجود.');
        }

        return $path;
    }

    public function delete(BackupLog $backup): void
    {
        $path = $this->resolveLocalBackupPath($backup);
        if (is_file($path) && ! @unlink($path)) {
            throw new RuntimeException('تعذر حذف ملف النسخة الاحتياطية.');
        }

        $backup->delete();
    }

    private function writeDatabaseJson(string $path): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('تعذر إنشاء ملف بيانات النسخة الاحتياطية.');
        }

        try {
            $meta = [
                'gym' => 'WINNER GYM',
                'created_at' => now(config('app.timezone'))->toIso8601String(),
                'database' => DB::connection()->getDriverName(),
                'version' => 2,
            ];

            $this->write($handle, '{"meta":'.$this->json($meta).',"tables":{');
            $firstTable = true;

            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                if (! $firstTable) {
                    $this->write($handle, ',');
                }
                $firstTable = false;
                $this->write($handle, $this->json($table).':[');

                $firstRow = true;
                DB::table($table)
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($handle, &$firstRow): void {
                        foreach ($rows as $row) {
                            if (! $firstRow) {
                                $this->write($handle, ',');
                            }
                            $firstRow = false;
                            $this->write($handle, $this->json((array) $row));
                        }
                    });

                $this->write($handle, ']');
            }

            $this->write($handle, '}}');
        } finally {
            fclose($handle);
        }
    }

    private function createArchive(string $archivePath, string $databaseFile): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذر إنشاء ملف النسخة الاحتياطية ZIP.');
        }

        try {
            $password = trim((string) config('winner-gym.backups.archive_password', ''));
            if ($password !== '') {
                $zip->setPassword($password);
            }

            $this->addArchiveFile($zip, $databaseFile, 'database.json', $password);
            $this->addStorageFiles($zip, storage_path('app/private'), 'files/private', $password, true);
            $this->addStorageFiles($zip, storage_path('app/public'), 'files/public', $password);
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('تعذر إغلاق ملف النسخة الاحتياطية ZIP.');
            }
        }
    }

    private function addStorageFiles(
        ZipArchive $zip,
        string $root,
        string $archivePrefix,
        string $password,
        bool $excludeBackups = false,
    ): void {
        if (! is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if ($excludeBackups && ($relative === 'backups' || str_starts_with($relative, 'backups/'))) {
                continue;
            }

            $this->addArchiveFile($zip, $file->getPathname(), "{$archivePrefix}/{$relative}", $password);
        }
    }

    private function addArchiveFile(ZipArchive $zip, string $source, string $archiveName, string $password): void
    {
        if (! $zip->addFile($source, $archiveName)) {
            throw new RuntimeException("تعذر إضافة {$archiveName} إلى النسخة الاحتياطية.");
        }

        if ($password !== '' && ! $zip->setEncryptionName($archiveName, ZipArchive::EM_AES_256, $password)) {
            throw new RuntimeException('مكتبة ZIP على الخادم لا تدعم تشفير AES-256 المطلوب.');
        }
    }

    private function pruneExpired(int $currentBackupId): void
    {
        $retentionDays = max(1, (int) config('winner-gym.backups.retention_days', 30));
        $threshold = now(config('app.timezone'))->subDays($retentionDays);

        BackupLog::query()
            ->whereKeyNot($currentBackupId)
            ->where('status', 'completed')
            ->where('completed_at', '<', $threshold)
            ->orderBy('id')
            ->chunkById(50, function ($backups): void {
                foreach ($backups as $backup) {
                    $this->delete($backup);
                }
            });
    }

    private function resolveLocalBackupPath(BackupLog $backup): string
    {
        $relative = str_replace('\\', '/', (string) ($backup->path ?: $backup->storage_path));
        if ($relative === '' || ! str_starts_with($relative, 'backups/') || str_contains($relative, '..')) {
            throw new RuntimeException('مسار النسخة الاحتياطية غير صالح.');
        }

        return storage_path('app/private/'.$relative);
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException("تعذر إنشاء المجلد: {$path}");
        }
    }

    /** @param resource $handle */
    private function write($handle, string $contents): void
    {
        if (fwrite($handle, $contents) === false) {
            throw new RuntimeException('تعذرت كتابة بيانات النسخة الاحتياطية.');
        }
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
