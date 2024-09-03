<?php

namespace App\Http\Controllers;

use App\Enum\TokenAbility;
use App\Enum\TokenName;
use App\Models\User;
use App\Providers\NicknameProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Faker\Generator as Faker;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Generate access and refresh tokens for the given user.
     *
     * @param User $user
     * @return array
     */
    private function generateTokens(User $user, $isAccessTokenRememberMe = false): array
    {
        $token = $user->createToken(isRememberMeToken: $isAccessTokenRememberMe);
        $accessToken = $token->plainTextToken;
        $refreshToken = $user->createRefreshToken($token->accessToken)->plainTextToken;

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'nickname' => 'required|string|max:20|min:3|unique:users|alpha_dash:ascii',
            'email' => 'string|email|max:200',
            'password' => [
                'confirmed',
                'string',
                'required',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                // ->uncompromised(3) TODO: find solution for unreliable test failing
            ],
        ]);

        $user = User::create([
            'nickname' => $request->nickname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tokens = $this->generateTokens($user);

        return response()->json([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token']
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        // check if login value is an email
        // If so, find corresponding user by their email
        // else attempt to find a user by their nickname
        $user = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $request->login)->first()
            : User::where('nickname', $request->login)->first();

        // password check
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tokens = $this->generateTokens($user, $request->remember_me ?? false);

        return response()->json([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token']
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
        ], 205);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);
        $user = $request->user();

        // password check
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        return response()->json([
        ], 205);
    }


    public function logoutAllCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // check if login value is an email
        // If so, find corresponding user by their email
        // else attempt to find a user by their nickname
        $user = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $request->login)->first()
            : User::where('nickname', $request->login)->first();

        // password check
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
                'password' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->tokens()->delete();
        return response()->json([
        ], 205);
    }

    public function checkNickname(string $nickname): JsonResponse
    {
        $validator = \Validator::make(['nickname' => $nickname], [
            'nickname' => 'required|alpha_dash:ascii|min:3|max:20|unique:users',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Nickname is available and without injection
        return response()->json([
            'available' => true,
            'nickname' => $nickname,
        ], 200);
    }

    public function generateNickname(): JsonResponse
    {

        $faker = app(Faker::class);
        $faker->addProvider(new NicknameProvider($faker));

        // how many times try to generate unique nickname before giving up and returning server error
        $maxRetries = 250;
        $retryCount = 0;
        $nickname = null;

        while ($retryCount < $maxRetries) {
            $nickname = $faker->nickname();

            if (!User::where('nickname', $nickname)->exists()) {
                break;
            }
            $retryCount++;
        }

        if ($retryCount >= $maxRetries) {
            throw new HttpResponseException(response()->json([
                'error' => 'Nickname generation failed after multiple attempts.'
            ], 503));
        }
        return response()->json([
            'nickname' => $nickname
        ], 200);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            // Get refresh token from request
            $refreshToken = PersonalAccessToken::findToken($request->bearerToken());
            if (!$refreshToken) {
                throw new \Exception('Refresh token not found.');
            }

            // Get user associated with refresh token and access token
            $user = $request->user();
            $accessToken = PersonalAccessToken::find($refreshToken->reference_token_id);
            if (!$accessToken) {
                throw new \Exception('Access token not found.' . $refreshToken->reference_token_id);
            }

            // Check if refresh token is connected to access token
            if (!$accessToken->can(TokenAbility::ACCESS_API->value)) {
                throw new \Exception('Refresh token was not connected to access token.');
            }

            // Check if the user associated with the access token matches the requesting user
            $accessTokenUser = $accessToken->tokenable;
            if ($accessTokenUser->id !== $user->id) {
                throw new \Exception('User mismatch between access token and requesting user.');
            }

        } catch (\Exception $e) {
            \Log::error('refresh-token error: ' . $e->getMessage());
            return response()->json(['message' => 'Unauthenticated.'], 422);
        }

        // After successfully checks remove old tokens and create new ones
        $rememberMe = $accessToken->name == TokenName::REMEMBER_ME_ACCESS_TOKEN->value;
        $refreshToken->delete();
        $accessToken->delete();

        $tokens = $this->generateTokens($user, $rememberMe);

        return response()->json([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token']
        ], 200);
    }
}
