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
        Schema::create('admin_configurations', function (Blueprint $table) {
            $table->id();
            $table->double('referral_point')->default(4);
            $table->double('deposit_charge')->default(1);
            $table->double('deposit_charge_waived_point')->default(0);
            $table->enum('deposit_type', ['percent', 'amount'])->default('percent');
            $table->double('withdraw_charge')->default(2.5);
            $table->double('withdraw_charge_waived_point')->default(20);
            $table->enum('withdraw_type', ['percent', 'amount'])->default('percent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_configurations');
    }
};
