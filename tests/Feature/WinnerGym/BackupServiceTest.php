<?php

namespace Tests\Feature\WinnerGym;

use App\Models\BackupLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_contains_database_and_private_attachments_with_a_complete_log(): void
    {
        config()->set('winner-gym.backups.archive_password', null);

        $owner = User::factory()->create(['role' => 'owner']);
        $fixtureDirectory = storage_path('app/private/test-backup-fixtures');
        $fixturePath = $fixtureDirectory.'/payment-proof.txt';
        $backup = null;

        if (! is_dir($fixtureDirectory)) {
            mkdir($fixtureDirectory, 0775, true);
        }
        file_put_contents($fixturePath, 'proof-content');

        try {
            $service = app(BackupService::class);
            $backup = $service->create($owner);
            $archivePath = $service->absolutePath($backup);

            $this->assertSame('completed', $backup->status);
            $this->assertSame($backup->file_name, $backup->filename);
            $this->assertSame($backup->storage_path, $backup->path);
            $this->assertSame($owner->id, $backup->initiated_by);
            $this->assertSame($owner->id, $backup->created_by);
            $this->assertGreaterThan(0, $backup->size_bytes);
            $this->assertFileExists($archivePath);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($archivePath) === true);
            $databaseJson = $zip->getFromName('database.json');
            $attachment = $zip->getFromName('files/private/test-backup-fixtures/payment-proof.txt');
            $zip->close();

            $this->assertNotFalse($databaseJson);
            $this->assertSame('proof-content', $attachment);
            $payload = json_decode((string) $databaseJson, true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(2, $payload['meta']['version']);
            $this->assertSame($owner->id, $payload['tables']['users'][0]['id']);
        } finally {
            if ($backup instanceof BackupLog && $backup->fresh()) {
                app(BackupService::class)->delete($backup->fresh());
            }
            if (is_file($fixturePath)) {
                @unlink($fixturePath);
            }
            if (is_dir($fixtureDirectory)) {
                @rmdir($fixtureDirectory);
            }
        }
    }
}
