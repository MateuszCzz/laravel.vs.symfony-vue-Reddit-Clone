<?php

namespace App\Models;

use App\Enum\PostType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $guarded = [
        'title',
        'creator_id',
        'subreddit_id',
        'type',
        'is_approved'
    ];

    protected $casts = [
        'is_nsfw' => 'boolean',
        'is_spoiler' => 'boolean',
        'is_locked' => 'boolean',
        'is_approved' => 'boolean',
        'type' => PostType::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function subreddit(): BelongsTo
    {
        return $this->belongsTo(Subreddit::class,'subreddit_id');
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
