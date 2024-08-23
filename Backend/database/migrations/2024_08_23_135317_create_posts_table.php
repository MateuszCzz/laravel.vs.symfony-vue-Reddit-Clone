<?php

use App\Enum\PostType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->text('content')->nullable();
            $table->enum('type', PostType::toArray());
            $table->boolean('is_approved')->default(true);//TODO
            $table->boolean('is_nsfw')->default(false);
            $table->boolean('is_spoiler')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('creator_id')->constrained('users')->nullable();
            $table->foreignId('subreddit_id')->constrained('subreddits')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
