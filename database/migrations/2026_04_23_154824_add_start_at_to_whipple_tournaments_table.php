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
        Schema::table('whipple_tournaments', function (Blueprint $table) {
            $table->timestamp('start_at')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('whipple_tournaments', function (Blueprint $table) {
            $table->dropColumn('start_at');
        });
    }
};
