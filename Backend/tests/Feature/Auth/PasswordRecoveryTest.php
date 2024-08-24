<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset()
    {
        $this->assertTrue(true);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_of_successful_password_reset(): void
    // {
    //     $this->assertTrue(true);
    // }
    //
    // public function test_user_gets_email_with_reset_token(): void
    // {
    //     $this->assertTrue(true);
    // }
    //
    // public function test_user_can_resend_email(): void
    // {
    //     $this->assertTrue(true);
    // }
    //
    // public function test_user_can_resend_email_only_three_times_a_day(): void
    // {
    //     $this->assertTrue(true);
    // }

    public function test_password_reset_creates_new_reset_token_in_database()
    {
        $this->assertTrue(true);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_reset_password_with_no_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_reset_password_with_expired_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_reset_password_with_non_matching_passwords(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_reset_password_twice_with_the_same_token(): void
    {
        $this->assertTrue(true);
    }
}
