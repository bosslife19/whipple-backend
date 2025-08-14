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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
             $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->enum('subcategory',['a1', 'a2', 'b', 'c1', 'c2', 'c3', 'single_dice', 'double_dice'])->nullable();
            $table->enum('coin_toss', ['heads', 'tails'])->nullable();
            $table->string('number_result')->nullable();
           
            $table->integer('dice_result')->nullable();
            $table->integer('odds')->nullable();
            $table->string('spin_wheel_result')->nullable();
            $table->integer('number_wheel_result')->nullable();
            $table->boolean('losers_game_won')->default(false);
            $table->boolean('game_won')->default(false);
            $table->string('number_wheel_results')->nullable();
            $table->integer('stake');
            $table->enum("winning_box", ['box1', 'box2', 'box3'])->nullable();
            $table->enum("spin_bottle", ['up', 'down'])->nullable();
            $table->enum('ball_direction', ['right', 'left', 'center'])->nullable();
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
