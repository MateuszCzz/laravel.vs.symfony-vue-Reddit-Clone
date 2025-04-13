<?php

namespace App\Http\Controllers;

use App\Enum\SubredditType;
use App\Models\Membership;
use App\Models\Subreddit;
use Illuminate\Http\Request;

class SubredditController extends Controller
{
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|max:21|min:3|string|alpha_dash|unique:subreddits',
            'description' => 'sometimes|string|max:500',
            'type' => ['sometimes', 'string', 'in:' . implode(',', SubredditType::values())],
            'is_nsfw' => 'sometimes|boolean',
        ]);

        // Create new subreddit from validated data
        $subreddit = Subreddit::create([
            'name' => $validated['name'],
            'description'=> $validated['description'] ?? null,
            'type' => $validated['type'] ?? SubredditType::PUBLIC,
            'is_nsfw' => $validated['is_nsfw'] ?? false,
            'creator_id' => $request->user()->id
        ]);

        // Add creator as new member
        $membership = Membership::create([
            'subreddit_id' => $subreddit->id,
            'member_id' => $request->user()->id,
        ]);

        // Provide feedback
        return response()->json([
            'subreddit' => $subreddit,
            'membership' => $membership
        ], 201);

    }
}