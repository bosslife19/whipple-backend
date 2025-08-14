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
        Schema::table('users', function (Blueprint $table) {
            $table->text("pin")->nullable();
            $table->string("referral_code")->nullable();
            $table->integer("referred_by")->nullable();
            $table->double("wallet_balance")->default(0);
            $table->double("whipple_point")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("pin");
            $table->dropColumn("referral_code");
            $table->dropColumn("referred_by");
            $table->dropColumn("wallet_balance");
            $table->dropColumn("whipple_point");
        });
    }
};
