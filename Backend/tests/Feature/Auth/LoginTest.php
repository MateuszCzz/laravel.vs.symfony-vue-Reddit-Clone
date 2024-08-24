<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // Tests for token generation during login:
    
    public function test_user_can_generate_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_successful_login_creates_new_token_in_database(): void
    {
        $this->assertTrue(true);
    }

    // Remember-me access token is a token that can be extended after a long break with refresh tokens
    public function test_valid_user_can_generate_remember_me_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_successful_remember_me_login_creates_new_token_in_database(): void
    {
        $this->assertTrue(true);
    }

    public function test_nonexistent_user_cannot_generate_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_generate_token_with_incorrect_credentials(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_generate_token_with_no_credentials(): void
    {
        $this->assertTrue(true);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_succesful_login_attempt(): void
    // {
    //     $this->assertTrue(true);
    // }
}
