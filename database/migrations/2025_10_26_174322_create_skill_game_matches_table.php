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
        Schema::create('skill_game_matches', function (Blueprint $table) {
            $table->id();
            $table->integer('game_id');
            $table->integer('max_players')->nullable();
            $table->integer('match_time_window')->nullable();
            $table->integer('countdown')->nullable();
            $table->enum('status', ['open', 'waiting', 'countdown', 'started', 'finished', 'cancelled'])->default('open');
            $table->bigInteger('pot_amount')->default(0);
            $table->decimal('platform_fee_percent', 5, 2)->default(20.00);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_game_matches');
    }
};
