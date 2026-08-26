<?php

namespace Tests\Feature\WinnerGym;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMemberInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_page_is_public(): void
    {
        $this->get('/member-inquiry')->assertOk();
    }

    public function test_invalid_member_code_does_not_expose_data(): void
    {
        $this->post('/member-inquiry', ['membership_code' => 'WG-NOTFOUND'])
            ->assertRedirect(route('member.inquiry'))
            ->assertSessionHasErrors('membership_code');
    }
}
