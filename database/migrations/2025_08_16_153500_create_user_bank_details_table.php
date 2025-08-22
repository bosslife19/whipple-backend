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
        Schema::create('user_bank_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('recipient_code')->nullable();
            $table->string('recipient_id')->nullable();
            $table->string('recipient_integration')->nullable();
            $table->string('recipient_type')->nullable();
            $table->json('recipient_detail')->nullable();
            $table->json('recipient_metal')->nullable();
            $table->enum('status', ['active', 'block'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bank_details');
    }
};
