<?php

namespace Tests\Feature\WinnerGym;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateGymOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_real_owner_without_a_seeded_test_user(): void
    {
        $this->artisan('winner-gym:create-owner', [
            'username' => 'final.owner',
            '--name' => 'مالك النظام',
            '--email' => 'owner@example.test',
            '--generate-password' => true,
        ])->assertSuccessful();

        $owner = User::query()->sole();

        $this->assertSame('owner', $owner->role);
        $this->assertTrue($owner->is_active);
        $this->assertTrue($owner->must_change_password);
        $this->assertNotSame('password', $owner->password);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
