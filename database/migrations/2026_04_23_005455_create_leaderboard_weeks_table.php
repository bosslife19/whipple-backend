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
        Schema::create('leaderboard_weeks', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->integer('top_rank')->default(32);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_weeks');
    }
};
