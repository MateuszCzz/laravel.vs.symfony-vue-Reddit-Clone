<?php

namespace App\Models;

use App\Enum\SubredditStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subreddit extends Model
{
    use HasFactory;

    protected $guarded = [
        'amount_of_members',
        'creator_id',
    ];

    protected $casts = [
        'status' => SubredditStatus::class,
        'send_welcome_message' => 'boolean',
        'is_nsfw' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
