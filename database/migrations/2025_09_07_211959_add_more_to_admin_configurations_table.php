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
        Schema::table('admin_configurations', function (Blueprint $table) {
            $table->double('no_question')->default(40);
            $table->double('award_point')->default(2);
            $table->double('allow_time')->default(5);
            $table->double('boost_time')->default(60);
            $table->double('boost_time_amount')->default(1000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_configurations', function (Blueprint $table) {
            $table->dropColumn('no_question');
            $table->dropColumn('award_point');
            $table->dropColumn('allow_time');
            $table->dropColumn('boost_time');
            $table->dropColumn('boost_time_amount');
        });
    }
};
