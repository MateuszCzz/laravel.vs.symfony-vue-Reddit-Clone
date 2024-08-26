<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function loginUserPost(string $login, string $password, $createUserWithNickname = false, $rememberMe = false, )
    {
        if ($createUserWithNickname) {
            User::factory()->create([
                'nickname' => $login,
                'password' => $password,
            ]);
        }

        return $this->postJson('/api/auth/login', [
            'login' => $login,
            'password' => $password,
            'remember_me' => $rememberMe,
        ]);
    }

    public function test_user_can_login_with_nickname(): void
    {
        $response = $this->loginUserPost('Test_user_1', 'P@ssword1', true);
        $response->assertJsonStructure([
            'user' => ['id', 'nickname', 'email'],
            'token',
        ]);
    }

    public function test_user_can_login_with_email(): void
    {
        User::factory()->create([
            'email' => 'test@email.com',
            'password' => 'P@ssword1',
        ]);

        $response = $this->loginUserPost('test@email.com', 'P@ssword1');
        $response->assertJsonStructure([
            'user' => ['id', 'nickname', 'email'],
            'token',
        ]);
    }

    public function test_successful_login_creates_new_token_in_database(): void
    {

        $response = $this->loginUserPost('Test_user_2', 'P@ssword1', true);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $response->json('user.id'),
            'tokenable_type' => 'App\\Models\\User',
            'name' => 'access-token',
        ]);
    }

    public function test_generated_tokens_are_time_limited(): void
    {
        $response = $this->loginUserPost('Test_user_3', 'P@ssword1', true);
        $token = \DB::table('personal_access_tokens')->where('tokenable_id', $response->json('user.id'))->first();
        $this->assertNotNull($token->expires_at, 'The token should have an expiration time set.');

        $expiresAt = Carbon::parse($token->expires_at);
        $this->assertTrue($expiresAt->lessThanOrEqualTo(Carbon::now()->addHour()), 'The token should expire within 1 hour.');
    }

    public function test_user_cannot_login_with_unregistered_nickname_or_email(): void
    {
        $response1 = $this->loginUserPost('test_unregistered@email.com', 'P@ssword1');
        $response1->assertJsonValidationErrors([
            'login' => 'The provided credentials are incorrect.',
            'password' => 'The provided credentials are incorrect.'
        ]);
        

        $response2 = $this->loginUserPost('test_registered', 'P@ssword1');
        $response2->assertJsonValidationErrors([
            'login' => 'The provided credentials are incorrect.',
            'password' => 'The provided credentials are incorrect.'
        ]);
        
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        User::factory()->create([
            'nickname' => 'Test_user_4',
            'password' => 'P@ssword2',
        ]);
        $response = $this->loginUserPost('Test_user_4', 'P@ssword1');
        $response->assertJsonValidationErrors([
            'login' => 'The provided credentials are incorrect.',
            'password' => 'The provided credentials are incorrect.'
        ]);
        
    }

    public function test_user_cannot_login_with_no_credentials(): void
    {
        User::factory()->create([
            'nickname' => 'Test_user_5',
            'password' => 'P@ssword2',
        ]);
        $response = $this->loginUserPost('Test_user_5', ' ');
        $response->assertJsonValidationErrors([
            'password' => 'The password field is required.'
        ]);
        
    }

    public function test_user_can_generate_remember_me_token(): void
    {
        $response = $this->loginUserPost('Test_user_6', 'P@ssword1', true, true);
        $response->assertJsonStructure([
            'user' => ['id', 'nickname', 'email'],
            'token',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $response->json('user.id'),
            'tokenable_type' => 'App\\Models\\User',
            'name' => 'remember_me_access_token'
        ]);
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_succesful_login_attempt(): void
    // {
    //     $this->assertTrue(true);
    // }
}
