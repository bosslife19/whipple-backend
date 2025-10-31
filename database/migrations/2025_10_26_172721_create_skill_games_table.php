<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_games', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // tap_rush, math_clash, color_switch, defuse_x
            $table->string('name')->nullable();
            $table->integer('stake'); // in smallest currency unit (e.g. kobo/ngn)
            $table->integer('rounds_question')->nullable(); // in smallest currency unit (e.g. kobo/ngn)
            $table->integer('duration_seconds')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // seed example games
        DB::table('skill_games')->insert([
            ['key' => 'tap_rush', 'name' => 'Tap Rush', 'stake' => 100, 'duration_seconds' => 30, 'rounds_question' => 20],
            ['key' => 'math_clash', 'name' => 'Math Clash', 'stake' => 300, 'duration_seconds' => 30, 'rounds_question' => 20],
            ['key' => 'color_switch', 'name' => 'Color Switch Reflex', 'stake' => 500, 'duration_seconds' => 20, 'rounds_question' => 20],
            ['key' => 'defuse_x', 'name' => 'Defuse-X', 'stake' => 1000, 'duration_seconds' => 13, 'rounds_question' => 20],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_games');
    }
};
