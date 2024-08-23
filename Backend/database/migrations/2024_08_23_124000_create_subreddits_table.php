<?php

use App\Enum\SubredditStatus;
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
        Schema::create('subreddits', function (Blueprint $table) {
            $table->id();
            $table->string('name', 21)->unique();
            $table->string('description', 500)->nullable();
            $table->enum('status', SubredditStatus::toArray())->default(SubredditStatus::PUBLIC->value);
            $table->boolean('send_welcome_message')->default(false);
            $table->text('welcome_message_text')->nullable();
            $table->boolean('is_nsfw')->default(false);
            $table->integer('amount_of_members')->default(0); //TODO
            $table->foreignId('creator_id')->constrained('users')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subreddits');
    }
};
