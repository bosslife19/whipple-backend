<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_settings', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();
        });

        $start = now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
        $end = now()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString();
        DB::table('leaderboard_settings')->insert([
            'period_start' => $start,
            'period_end' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_settings');
    }
};
