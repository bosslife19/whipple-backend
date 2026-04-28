<?php

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
        Schema::create('leaderboard_pauses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leaderboard_week_id')->constrained('leaderboard_weeks')->onDelete('cascade');
            $table->dateTime('paused_at');
            $table->dateTime('resumed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_pauses');
    }
};
