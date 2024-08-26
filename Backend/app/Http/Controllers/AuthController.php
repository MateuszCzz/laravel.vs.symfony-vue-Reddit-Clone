<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\NicknameProvider;
use GuzzleHttp\Exception\RequestException;
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
        ]);

        // check if login is email and find coresponding user else its nickname or just wrong

        $user = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? User::where('email', $request->login)->first()
            : User::where('nickname', $request->login)->first();

        //password check
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

    public function logout()
    {
        //
    }

    public function logoutAll()
    {
        //
    }

    public function checkNickname(Request $request)
    {
        $request->validate([
            'nickname' => 'required|alpha_dash:ascii|unique:users',
        ]);

        // Nickname is available and without injection
        return response()->json([
            'available' => true
        ], 200);
    }

    public function generateNickname()
    {

        $faker = app(Faker::class);
        $faker->addProvider(new NicknameProvider($faker));

        //how many time to try to generate unique nickname before giving error
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
