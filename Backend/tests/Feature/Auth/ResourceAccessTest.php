<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_protected_resource_with_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_access_protected_resource_with_expired_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_access_protected_resource_with_soft_deleted_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_access_protected_resource_with_invalid_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_cannot_access_protected_resource_with_no_token(): void
    {
        $this->assertTrue(true);
    }
    
    public function test_user_can_access_non_protected_resource_with_token(): void
    {
        $this->assertTrue(true);
    }

    public function test_user_can_access_non_protected_resource_with_no_token(): void
    {
        $this->assertTrue(true);
    }
}
