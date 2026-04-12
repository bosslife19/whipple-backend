<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title')->default('Whipple Tournament');
            $table->string('status', 32)->default('draft');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('manual');
            $table->unsignedInteger('import_rank')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->boolean('screen_share_enabled')->default(false);
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
        });

        Schema::create('tournament_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round_number')->default(1);
            $table->string('game_type', 64);
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('countdown_seconds')->default(20);
            $table->timestamp('countdown_ends_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('tournament_round_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_round_id')->constrained('tournament_rounds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->string('time')->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->boolean('left_early')->default(false);
            $table->timestamp('finished_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tournament_round_id', 'user_id']);
        });

        Schema::create('tournament_commentaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_key_moment')->default(false);
            $table->timestamps();
        });

        Schema::create('tournament_screen_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tournament_id', 'slot']);
        });

        Schema::create('admin_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->decimal('amount', 15, 2);
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_balance_adjustments');
        Schema::dropIfExists('tournament_screen_shares');
        Schema::dropIfExists('tournament_commentaries');
        Schema::dropIfExists('tournament_round_scores');
        Schema::dropIfExists('tournament_rounds');
        Schema::dropIfExists('tournament_participants');
        Schema::dropIfExists('tournaments');
    }
};
