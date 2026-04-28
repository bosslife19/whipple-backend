<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [ 
        'skill_game_matches',
        'skill_game_match_players',
        'quiz_sessions',
        'quiz_answers',
        'forecasts',
        'forecast_rounds',
        'transactions'
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {

                if (!Schema::hasColumn($table->getTable(), 'week_id')) {
                    $table->unsignedBigInteger('week_id')
                          ->nullable()
                          ->after('id');
                }

                if (!Schema::hasColumn($table->getTable(), 'user_type')) {
                    $table->enum('user_type', ['user', 'virtual'])
                          ->default('user')
                          ->after('week_id'); // ✅ FIXED position
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {

                if (Schema::hasColumn($table->getTable(), 'week_id')) {
                    $table->dropColumn('week_id');
                }

                if (Schema::hasColumn($table->getTable(), 'user_type')) {
                    $table->dropColumn('user_type');
                }
            });
        }
    }
};