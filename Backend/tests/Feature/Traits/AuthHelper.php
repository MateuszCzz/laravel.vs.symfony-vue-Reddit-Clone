<?php

namespace Tests\Feature\Traits;

use App\Enum\TokenAbility;
use App\Enum\TokenName;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

trait AuthHelper
{
    // Routes:
    private const LOGIN_ROUTE = '/api/auth/login';
    private const LOGOUT_ROUTE = '/api/auth/logout';
    private const LOGOUT_ALL_ROUTE = '/api/auth/logout-all';
    private const REFRESH_TOKEN_ROUTE = '/api/auth/refresh-token';

    // Status codes:
    private const VALIDATION_ERROR_STATUS = 422;
    private const SUCCESSFUL_LOGOUT_STATUS = 205;
    private const AUTH_FAILED_STATUS = 401;

    // JSON structures:
    private const SUCCESSFUL_AUTH_JSON_STRUCTURE = [
        'user' => ['id', 'nickname', 'email'],
        'access_token',
        'refresh_token',
    ];

    // User data:
    private const USER_PASSWORD_DEFAULT = 'P@ssword1';
    private const USER_NICKNAME_DEFAULT = 'test_user_nickname';
    private const USER_EMAIL_DEFAULT = 'test_user_email@example.com';
    private const TEST_NICKNAME_SHORT = 'te';
    private const TEST_NICKNAME_LONG = 'test_user_nickname_that_is_too_long';
    private const TEST_NICKNAME_SPECIAL_CHARACTERS = 'test_user_nickname$#';

    /**
     * Generate a new user.
     *
     * @param string $nickname The nickname of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @return \App\Models\User The created User instance.
     */
    private function createUser(string $nickname = self::USER_NICKNAME_DEFAULT, string $email = self::USER_EMAIL_DEFAULT, string $password = self::USER_PASSWORD_DEFAULT): User
    {
        return User::factory()->create([
            'nickname' => $nickname,
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Generate a new access token for the given user.
     *
     * @param User|null $user The user for whom the access token is being created. When not provided new user will be generated using factory.
     * @param bool $isExpired Whether the token should be expired immediately.
     * @param bool $toReturnPlainToken Whether to return the plain text token or the token model.
     * @param bool $isRememberMe Whether remember-me token should be created.
     * @return string|array The access token in plain text format or array containing both the token model and plain text token.
     */
    private function createAccessToken(?User $user = null, bool $isExpired = false, bool $toReturnPlainToken = true, bool $isRememberMe = false): string|array
    {
        $user ??= User::factory()->create();

        $plainTextToken = $user->generateTokenString();
        $token = $user->tokens()->create([
            'name' => $isRememberMe ? TokenName::REMEMBER_ME_ACCESS_TOKEN->value : TokenName::ACCESS_TOKEN->value,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => [TokenAbility::ACCESS_API->value],
            'expires_at' => $isExpired ? now()->subCentury() : now()->addHour(),
        ]);

        return $toReturnPlainToken ? $token->getKey() . '|' . $plainTextToken : [$token, $token->getKey() . '|' . $plainTextToken];
    }

    /**
     * Make a POST request to login with user data.
     *
     * @param bool $loginWithNickname Whether to login with nickname (true) or email (false).
     * @param string $nickname The nickname of the user.
     * @param string $email The email address of the user.
     * @param string $password The password of the user.
     * @param bool $rememberMe Whether to set the "remember me" flag.
     * @return \Illuminate\Testing\TestResponse The response from the login request.
     */
    private function loginUserPost(bool $loginWithNickname = true, string $nickname = self::USER_NICKNAME_DEFAULT, string $email = self::USER_EMAIL_DEFAULT, string $password = self::USER_PASSWORD_DEFAULT, bool $rememberMe = false): TestResponse
    {
        return $this->postJson(self::LOGIN_ROUTE, [
            'login' => $loginWithNickname ? $nickname : $email,
            'password' => $password,
            'remember_me' => $rememberMe,
        ]);
    }

    /**
     * Make a POST request to logout given user.
     *
     * @param string $token The authorization token of the user performing the logout.
     * @param bool $remove_all Whether to remove all sessions for the user. If true, the user's password must be provided.
     * @param string $password The password of the user.
     * @return \Illuminate\Testing\TestResponse The response from the logout request.
     */
    private function logoutUserPost(string $token, bool $remove_all = false, string $password = self::USER_PASSWORD_DEFAULT): TestResponse
    {
        $data = [];

        // Set data for request
        if ($remove_all) {
            $data['password'] = $password;
        }
        $route = $remove_all ? self::LOGOUT_ALL_ROUTE : self::LOGOUT_ROUTE;

        return $this->postJson($route, $data, [
            'Authorization' => "Bearer $token",
        ]);
    }

    /**
     * Find personal access tokens for a specific user.
     *
     * @param int $userId The ID of the user to search for.
     * @param string $tokenName The name of the token to search for.
     * @return Collection Collection of personal access tokens matching the criteria.
     */
    private function findUserTokens(int $userId, string $tokenName = TokenName::ACCESS_TOKEN->value): Collection
    {
        return PersonalAccessToken::where([
            ['tokenable_id', $userId],
            ['name', $tokenName],
            ['tokenable_type', User::class]
        ])->get();
    }

}
