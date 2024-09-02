<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use App\Enum\TokenAbility;
use App\Enum\TokenName;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * Simplified Override from HasApiTokens trait.
     * Create a new access token based on enum values and config settings.
     * @param bool|null  $isRememberMeToken Whether to create a "remember me" token.
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function createToken(bool $isRememberMeToken = false): NewAccessToken
    {
        $tokenName = $isRememberMeToken ? TokenName::REMEMBER_ME_ACCESS_TOKEN->value : TokenName::ACCESS_TOKEN->value;
        $expiresAt = config('sanctum.ac_expiration') == null ? null : now()->addMinutes(config('sanctum.ac_expiration'));

        $plainTextToken = $this->generateTokenString();
        $token = $this->tokens()->create([
            'name' => $tokenName,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => [TokenAbility::ACCESS_API->value],
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    /**
     * Create a new refresh token.
     * @param  \Laravel\Sanctum\PersonalAccessToken  $referenceToken Token the newly created refresh token will reference.
     * @return \Laravel\Sanctum\NewAccessToken|null
     */
    public function createRefreshToken(PersonalAccessToken $referenceToken): NewAccessToken|null
    {
        // Set token params based on the type of referenced token
        switch ($referenceToken->name) {
            case TokenName::ACCESS_TOKEN->value:
                $expiresAt = config('sanctum.rf_expiration');
                $tokenName = TokenName::REFRESH_TOKEN->value;
                break;

            case TokenName::REMEMBER_ME_ACCESS_TOKEN->value:
                $expiresAt = config('sanctum.remember_me_rf_expiration');
                $tokenName = TokenName::REMEMBER_ME_REFRESH_TOKEN->value;
                break;

            default:
                // Wrong token was passed in reference 
                return null;
        }

        // If config value is null, set expiration to null
        $expiresAt = $expiresAt !== null ? now()->addMinutes($expiresAt) : null;

        $plainTextToken = $this->generateTokenString();
        $token = $this->tokens()->create([
            'name' => $tokenName,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => [TokenAbility::REFRESH_EXPIRATION->value],
            'expires_at' => $expiresAt,
            'reference_token_id' => $referenceToken->id,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nickname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function createdSubreddits(): HasMany
    {
        return $this->hasMany(Subreddit::class);
    }

    public function createdPosts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function createdComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
