<?php

use App\Enum\SubredditType;
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
            $table->enum('type', SubredditType::values())->default(SubredditType::PUBLIC->value);
            $table->boolean('is_nsfw')->default(false);
            // $table->integer('amount_of_members')->default(0); //TODO
            $table->foreignId('creator_id')->constrained('users');
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
