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
        Schema::create('forecast_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->foreignId('user_id')->constrained();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->enum('type', ['general', 'specific'])->default('general');
            $table->integer('winnings')->default(0);            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_rounds');
    }
};
