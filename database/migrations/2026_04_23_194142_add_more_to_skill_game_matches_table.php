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
        Schema::table('skill_game_matches', function (Blueprint $table) {
            $table->enum('game_type',['direct','tournament'])->default('direct');
            $table->integer('tournament_id')->nullable();
            $table->integer('lobby_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skill_game_matches', function (Blueprint $table) {
            $table->dropColumn('game_type');
            $table->dropColumn('tournament_id');
            $table->dropColumn('lobby_id');
        });
    }
};
