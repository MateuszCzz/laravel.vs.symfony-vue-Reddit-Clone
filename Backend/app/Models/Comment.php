<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [
        'is_sticky',
        'is_locked',
        'is_approved',
        'is_mod',
        'is_subcomment',
        'parent_comment_id',
        'creator_id',
        'post_id',
    ];


    protected $casts = [
        'is_sticky' => 'boolean',
        'is_locked' => 'boolean',
        'is_approved' => 'boolean',
        'is_mod' => 'boolean',
        'is_subcomment' => 'boolean',
    ];

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function childComments(): HasMany
    {
        return $this->hasMany(self::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
