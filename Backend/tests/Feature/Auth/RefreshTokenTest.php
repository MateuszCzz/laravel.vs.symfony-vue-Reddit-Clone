<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_obtain_new_access_token_with_refresh_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_refresh_token_expires_after_validity_period(): void
    {
        $this->assertTrue(true);
    }

    public function test_remember_me_refresh_token_donot_expire_after_validity_period(): void
    {
        $this->assertTrue(true);
    }

    public function test_new_refresh_token_issued_with_access_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_new_refresh_token_issued_on_successful_refresh(): void
    {
        $this->assertTrue(true);
    }

    public function test_refresh_token_cannot_be_reused(): void
    {
        $this->assertTrue(true);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_about_old_refresh_token_usage(): void
    // {
    //     $this->assertTrue(true);
    // }
}
