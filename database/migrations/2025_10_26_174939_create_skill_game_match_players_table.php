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
        Schema::create('skill_game_match_players', function (Blueprint $table) {
            $table->id();
            $table->integer('match_id');
            $table->integer('user_id');
            $table->bigInteger('stake_paid');
            $table->enum('status', ['joined', 'ready', 'eliminated', 'finished'])->default('joined');
            $table->boolean('has_submitted')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->json('scores')->nullable();
            $table->integer('score')->default(0);
            $table->double('winnings')->default(0);
            $table->integer('rank')->nullable();
            $table->string('time')->nullable();
            $table->bigInteger('payout')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_game_match_players');
    }
};
