<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whipple_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Whipple Tournament');
            $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('whipple_tournament_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('whipple_tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['frequent', 'wins', 'manual'])->default('manual');
            $table->unsignedInteger('import_rank')->nullable();
            $table->boolean('eliminated')->default(false);
            $table->timestamp('eliminated_at')->nullable();
            $table->boolean('screen_share_ack')->default(false);
            $table->timestamps();
            $table->unique(['tournament_id', 'user_id']);
        });

        Schema::create('whipple_tournament_lobbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('whipple_tournaments')->cascadeOnDelete();
            $table->string('game_key'); // quiz, tap_rush, math_clash, color_switch, defuse_x
            $table->string('label')->nullable();
            $table->enum('status', ['pending', 'countdown', 'live', 'ended'])->default('pending');
            $table->unsignedSmallInteger('countdown_seconds')->default(20);
            $table->timestamp('countdown_started_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('whipple_tournament_lobby_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained('whipple_tournament_lobbies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 12, 2)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['lobby_id', 'user_id']);
        });

        Schema::create('whipple_tournament_commentary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('whipple_tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_key_moment')->default(false);
            $table->timestamps();
        });

        Schema::create('whipple_tournament_stream_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('whipple_tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot')->default(1); // 1 or 2
            $table->timestamps();
            $table->unique(['tournament_id', 'slot']);
            $table->unique(['tournament_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whipple_tournament_stream_slots');
        Schema::dropIfExists('whipple_tournament_commentary');
        Schema::dropIfExists('whipple_tournament_lobby_scores');
        Schema::dropIfExists('whipple_tournament_lobbies');
        Schema::dropIfExists('whipple_tournament_players');
        Schema::dropIfExists('whipple_tournaments');
    }
};
