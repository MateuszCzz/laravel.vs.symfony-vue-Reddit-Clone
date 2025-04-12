<?php

namespace Tests\Feature\Auth;

use App\Enum\TokenName;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Traits\AuthHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Testing\TestResponse;
class RefreshTokenTest extends TestCase
{
    use RefreshDatabase, AuthHelper;
    
    private const SUCCESSFUL_REFRESH_STATUS = 200;

    public static function tokenDataProvider(): array
    {
        return [
            'Refresh Token' => ['rememberMe' => false, 'tokenName' => TokenName::REFRESH_TOKEN,],
            'Remember Refresh Token' => ['rememberMe' => true, 'tokenName' => TokenName::REMEMBER_ME_REFRESH_TOKEN,],
        ];
    }

    /**
     * Make a POST request to refresh with refresh token.
     *
     * @param string $token The refresh token to use for authentication.
     * @return TestResponse The response from the refresh token request.
     */
    private function refreshTokenPost(string $token): TestResponse
    {
        return $this->postJson(self::REFRESH_TOKEN_ROUTE, [], [
            'Authorization' => "Bearer $token",
        ]);
    }

    /**
     * Assert that the new token has replaced the old token.
     *
     * This method checks if only one token exists, and that the
     * new token is not the same as the old one.
     *
     * @param \Laravel\Sanctum\PersonalAccessToken $oldToken
     * @param \Illuminate\Database\Eloquent\Collection $newToken
     * @return void
     */
    private function assertTokenReplaced($oldToken, $newToken): void
    {
        $this->assertEquals(1, $newToken->count(), 'Only one token should exist.');
        $this->assertNotEquals($oldToken->id, $newToken->first()->id, 'The token should have been replaced.');
    }

    /**
     * Generate a new access token for the given user.
     * 
     * @param bool $isAccessTokenExpired Whether the token should be expired immediately.
     * @param bool $isAccessTokenRememberMe Whether remember-me token should be created.
     * @return array The access token in plain text format or the token model.
     */
    private function createTokens(bool $isAccessTokenExpired = false, bool $isAccessTokenRememberMe = false): array
    {
        $user = $this->createUser();
        $accessToken = $this->createAccessToken($user, $isAccessTokenExpired, $isAccessTokenRememberMe);
        $refreshToken = $user->createRefreshToken($accessToken[0])->plainTextToken;

        return [
            'access_token' => $accessToken[1],
            'refresh_token' => $refreshToken
        ];
    }

    #[Test]
    #[DataProvider('tokenDataProvider')]
    public function test_refresh_token_created_with_access_token(bool $rememberMe, TokenName $tokenName): void
    {
        $this->createUser();
        $response = $this->loginUserPost(rememberMe: $rememberMe);

        $this->assertEquals(
            1,
            $this->findUserTokens(
                $response->json('user.id'),
                $tokenName->value
            )->count(),
            'Token should exist.'
        );
    }

    #[Test]
    #[DataProvider('tokenDataProvider')]
    public function test_refresh_tokens_are_time_limited(bool $rememberMe, TokenName $tokenName): void
    {
        $this->createUser();
        $response = $this->loginUserPost(rememberMe: $rememberMe);

        // Find newly created token
        $token = $this->findUserTokens(
            $response->json('user.id'),
            $tokenName->value
        )->first();

        // Check if token has right expiration time based on configuration
        $expiration = $rememberMe ?
            config('sanctum.remember_me_rf_expiration')
            : config('sanctum.rf_expiration');

        if (is_null($expiration)) {
            $this->assertNull($token->expires_at, 'Token should never expire.');
        } else {
            $expiresAt = Carbon::parse($token->expires_at);
            $this->assertTrue(
                $expiresAt->lessThanOrEqualTo(Carbon::now()->addMinutes($expiration)),
                'The token should expire within configured time.'
            );
        }
    }

    #[Test]
    #[DataProvider('tokenDataProvider')]
    public function test_refresh_token_created_on_refreshing(bool $rememberMe, TokenName $tokenName): void
    {
        $this->createUser();
        $response = $this->loginUserPost(rememberMe: $rememberMe);
        $refreshToken = PersonalAccessToken::findToken($response->json('refresh_token'));

        $response2 = $this->refreshTokenPost($response->json('refresh_token'))
            ->assertJsonStructure(self::SUCCESSFUL_AUTH_JSON_STRUCTURE)
            ->assertStatus(self::SUCCESSFUL_REFRESH_STATUS);

        $newToken = $this->findUserTokens(
            $response2->json('user.id'),
            $tokenName->value
        );
        $this->assertTokenReplaced($refreshToken, $newToken);
    }

    #[Test]
    public function test_user_can_refresh_expired_access_token(): void
    {
        $tokenArray = $this->createTokens(true);
        $accessToken = PersonalAccessToken::findToken($tokenArray['access_token']);
        $response = $this->refreshTokenPost($tokenArray['refresh_token']);

        $newToken = $this->findUserTokens(
            $response->json('user.id'),
            TokenName::ACCESS_TOKEN->value
        );

        $this->assertTokenReplaced($accessToken, $newToken);
    }

    #[Test]
    public function test_user_can_only_refresh_referenced_access_token(): void
    {
        $tokenArray = $this->createTokens(true);

        // Create second access token
        $accessToken = $this->createAccessToken();

        $this->refreshTokenPost($tokenArray['refresh_token']);

        // Check if the access token is still in database
        $this->assertNotNull(
            PersonalAccessToken::findToken($accessToken),
            'The second access token should exist in the database.'
        );
    }

    #[Test]
    public function test_refresh_tokens_are_removed_with_access_tokens(): void
    {
        $tokenArray = $this->createTokens(true);
        PersonalAccessToken::findToken($tokenArray['access_token'])->delete();

        // Check if the refresh token was removed with access token
        $this->assertNull(
            PersonalAccessToken::findToken($tokenArray['refresh_token']),
            'The second access token should exist in the database.'
        );
    }

    #[Test]
    public function test_only_referenced_refresh_tokens_removed_with_access_token(): void
    {
        $tokenArray = $this->createTokens(true);

        // Create and remove new access token
        PersonalAccessToken::findToken($this->createAccessToken())->delete();

        // Check if the refresh token was not removed with access token
        $this->assertNotNull(
            PersonalAccessToken::findToken($tokenArray['refresh_token']),
            'The second access token should exist in the database.'
        );
    }

    // TODO: Implement mailing system
    // public function test_user_is_notified_about_old_refresh_token_usage(): void
    // {
    //     $this->assertTrue(true);
    // }
}