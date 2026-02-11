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
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('match_id')->constrained();
            $table->foreignId('forecast_round_id')->constrained();
            $table->enum('choice_a', ['win', 'draw', 'loss'])->nullable();
            $table->enum('choice_b', ['win', 'draw', 'loss'])->nullable();
            $table->integer('score_a')->nullable();
            $table->integer('score_b')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->enum('status', ['pending', 'scored'])->default('pending'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};
