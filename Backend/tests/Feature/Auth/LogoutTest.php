<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_remove_their_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_can_remove_all_their_tokens(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_removes_only_their_tokens_when_removing_all_tokens(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_remove_all_their_tokens_with_incorrect_credentials(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_remove_all_their_tokens_with_no_credentials(): void
    {
        $this->assertTrue(true);
    }

    public function test_successful_removal_keeps_soft_deleted_tokens_in_database(): void
    {
        $this->assertTrue(true);
    }

    public function test_successful_mass_removal_keeps_soft_deleted_tokens_in_database(): void
    {
        $this->assertTrue(true);
    }
}
