<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function logoutUserPost(string $token, string $password = null, bool $remove_all = false)
    {
        $data = [];
        if ($password !== null && $remove_all == true) {
            $data['password'] = $password;
        }
        $route = $remove_all ? '/api/auth/logout-all' : '/api/auth/logout';
        return $this->postJson( $route, $data, [
            'Authorization' => "Bearer $token",
        ]);
    }

    private function loginUserPost()
    {
        $user = User::factory()->create();

        return $this->postJson('/api/auth/login', [
            'login' => $user->nickname,
            'password' => 'Password1',
        ]);
    }

    public function test_user_can_remove_their_token(): void
    {
        $userLoginRequest = $this->loginUserPost();
        $userLoginRequest->assertOk();

        //check if token is in database
        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNotNull($tokenRecord, 'The token should be in the database.');

        $response = $this->logoutUserPost($token);
        $response->assertStatus(205);

        //check if token is in database
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNull($tokenRecord, 'The token should be removed from the database.');
    }


    public function test_user_can_remove_all_their_tokens(): void
    {
        $userLoginRequest = $this->loginUserPost();
        $userLoginRequest->assertOk();
        $userId = $userLoginRequest->json('user.id');

        //create second token
        User::find($userLoginRequest->json('user.id'))->createToken('token_2_test');

        $response = $this->logoutUserPost($userLoginRequest->json('token'), 'Password1', true);
        $response->assertStatus(205);
        $tokens = \DB::table('personal_access_tokens')->where('tokenable_id', $userId)->get();

        // Verify that no tokens are left in the database
        $tokens = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->get();
        $this->assertEmpty($tokens, 'All tokens should be removed from the database.');

    }

    public function test_user_removes_only_their_tokens_when_removing_all_tokens(): void
    {
        $user1LoginRequest = $this->loginUserPost();
        $user2LoginRequest = $this->loginUserPost();
        $user1Id = $user1LoginRequest->json('user.id');

        User::find($user1Id)->createToken('token_2_test');

        $response = $this->logoutUserPost($user1LoginRequest->json('token'), 'Password1', true);
        $response->assertStatus(205);

        // Verify that all user1 tokens are removed
        $tokens = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $user1Id)
            ->get();
        $this->assertEmpty($tokens, 'All tokens should be removed from the database.');

        // Verify that user2 token have not
        $user2Token = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $user2LoginRequest->json('user.id'))
            ->get()->first();
        $this->assertNotNull($user2Token, 'The token should not be removed.');
    }

    public function test_user_cannot_remove_all_their_tokens_with_incorrect_credentials(): void
    {
        $userLoginRequest = $this->loginUserPost();
        $userLoginRequest->assertOk();

        //check if token is in database
        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNotNull($tokenRecord, 'The token should be in the database.');

        $response = $this->logoutUserPost($token, 'Password2', true);
        $response->assertJsonValidationErrors([
            'password' => ['The provided credentials are incorrect.'],
        ]);

        //check if token is in database
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNotNull($tokenRecord, 'The token should not be removed from the database.');
    }

    public function test_user_cannot_remove_all_their_tokens_with_no_credentials(): void
    {
        $userLoginRequest = $this->loginUserPost();
        $userLoginRequest->assertOk();

        //check if token is in database
        $token = $userLoginRequest->json('token');
        $userId = $userLoginRequest->json('user.id');
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNotNull($tokenRecord, 'The token should be in the database.');

        $response = $this->logoutUserPost($token, '', true);
        $response->assertJsonValidationErrors([
            'password' => ['The password field is required.'],
        ]);

        //check if token is in database
        $tokenRecord = \DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->first();
        $this->assertNotNull($tokenRecord, 'The token should not be removed from the database.');
    }

    //TODO: implement refresh tokens and deal with them on logout too or implement cascade 
}
