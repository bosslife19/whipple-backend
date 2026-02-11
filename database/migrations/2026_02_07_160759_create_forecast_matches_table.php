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
        Schema::create('forecast_matches', function (Blueprint $table) {
            $table->id();
            $table->string('team_logo_a');
            $table->string('team_logo_b');
            $table->string('team_name_a');
            $table->string('team_name_b');
            $table->dateTime('kickoff_time');
            $table->enum('type', ['general', 'specific'])->default('general');
            $table->enum('result_a', ['win', 'draw', 'loss'])->nullable();
            $table->enum('result_b', ['win', 'draw', 'loss'])->nullable();
            $table->integer('score_a')->nullable();
            $table->integer('score_b')->nullable();
            $table->enum('status', ['draft', 'active', 'ended'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_matches');
    }
};
