<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\NicknameProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Faker\Generator as Faker;



/**
 * @OA\Info(
 *     title="Reddit based API",
 *     version="1.0.0",
 *     description="API documentation for my application",
 * )
 */
class AuthController extends Controller
{
    public function register(Request $request)
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

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('token-name')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        // check if login value is an email 
        // If so, find coresponding user by their email 
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

        return response()->json([
            'user' => $user,
            'token' => $user->createToken(
                $request->remember_me ? 'remember_me_access_token' : 'access-token',
                ['*'],
                now()->addHour()
            )
                ->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
        ], 205);
    }

    public function logoutAll(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
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

    public function checkNickname(string $nickname)
    {
        $validator = \Validator::make(['nickname' => $nickname], [
            'nickname' => 'required|alpha_dash:ascii|min:3|max:20|unique:users',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Nickname is available and without injection
        return response()->json([
            'available' => true
        ], 200);
    }

    public function generateNickname()
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

    public function checkToken()
    {
        //
    }

}
