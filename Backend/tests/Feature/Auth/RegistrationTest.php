<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerUserPost(string $nickname, string $email, string $password, string $password_confirmation)
    {
        return $this->postJson('/api/auth/register', [
            'nickname' => $nickname,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password_confirmation,
        ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->registerUserPost('testregister1', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response->assertJsonStructure([
            'user' => ['id', 'nickname', 'email'],
            'token',
        ]);
    }

    public function test_successful_registration_creates_new_user_in_database(): void
    {
        $this->registerUserPost('testregister2', 'testuser@example.com', 'P@ssword1', 'P@ssword1');

        $this->assertDatabaseHas('users', [
            'nickname' => 'testregister2',
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_nickname(): void
    {
        $this->registerUserPost('testregister3', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response = $this->registerUserPost('testregister3', 'testuser2@example.com', 'P@ssword12', 'P@ssword12');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname has already been taken.'
        ]);
    }

    public function test_user_cannot_register_with_too_short_or_too_long_nickname(): void
    {
        $response = $this->registerUserPost('ab', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must be at least 3 characters.'
        ]);

        $response = $this->registerUserPost('abbbbbbbbbbbbbbbbbbbbbbbbb', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must not be greater than 20 characters.'
        ]);
    }

    public function test_user_cannot_register_with_special_characters_in_nickname(): void
    {
        $response = $this->registerUserPost('testregister#$%@', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
        ]);
    }

    public function test_user_cannot_register_with_missing_credentials(): void
    {
        $response = $this->registerUserPost('testregister5', 'testuser@example.com', '', '');

        $response->assertJsonValidationErrors([
            'password' => 'The password field is required.'
        ]);
        $response->assertJsonValidationErrors([
            'password' => 'The password field must be a string.'
        ]);

        $response = $this->registerUserPost('', 'testuser@example.com', 'P@ssword1', 'P@ssword1');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field is required.'
        ]);
    }

    public function test_user_can_check_if_nickname_is_available(): void
    {
        $response = $this->getJson('/api/auth/check-nickname/testregister6');

        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

    public function test_nickname_availability_when_taken(): void
    {
        User::factory()->create([
            'nickname' => 'testregister6',
        ]);

        $response = $this->getJson('/api/auth/check-nickname/testregister6');

        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname has already been taken.'
        ]);
    }

    public function test_nickname_availability_for_nothing(): void
    {
        $response = $this->getJson('/api/auth/check-nickname/ ');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field is required.'
        ]);
    }

    public function test_nickname_availability_with_special_characters(): void
    {
        $response = $this->getJson('/api/auth/check-nickname/invalid@$nickname');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must only contain letters, numbers, dashes, and underscores.'
        ]);
    }

    public function test_nickname_availability_with_too_short_or_too_long_nickname(): void
    {
        $response = $this->getJson('/api/auth/check-nickname/te');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must be at least 3 characters.'
        ]);
        $response = $this->getJson('/api/auth/check-nickname/toolongnicknameaaaaaaaaaaaaaaaaaaa');
        $response->assertJsonValidationErrors([
            'nickname' => 'The nickname field must not be greater than 20 characters.'
        ]);
    }

    public function test_user_can_generate_unique_nickname(): void
    {
        $nicknameRequest = $this->postJson('/api/auth/generate-nickname');
        $nicknameRequest->assertOk();

        $response = $this->getJson('/api/auth/check-nickname/' . $nicknameRequest->json('nickname'));
        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

    public function test_user_can_generate_unique_nickname_when_large_number_of_users(): void
    {
        User::factory()->createMany(100);
        $nicknameRequest = $this->postJson('/api/auth/generate-nickname/');

        $response = $this->getJson('/api/auth/check-nickname/' . $nicknameRequest->json('nickname'));
        $response->assertOk()
            ->assertJson([
                'available' => true
            ]);
    }

}
