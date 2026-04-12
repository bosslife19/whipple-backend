<?php

namespace App\Http\Controllers;

use App\Http\Services\TournamentService;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\TournamentRound;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournaments
    ) {}

    /** Active tournaments the authenticated user participates in. */
    public function mine(Request $request): JsonResponse
    {
        $ids = TournamentParticipant::query()
            ->where('user_id', $request->user()->id)
            ->pluck('tournament_id');

        $list = Tournament::query()
            ->whereIn('id', $ids)
            ->whereIn('status', ['draft', 'active'])
            ->orderByDesc('id')
            ->get(['id', 'uuid', 'title', 'status', 'created_at']);

        return response()->json(['status' => true, 'tournaments' => $list]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $tournament = Tournament::query()->where('uuid', $uuid)->firstOrFail();
        $this->tournaments->ensureParticipant($tournament, $request->user()->id);

        return response()->json([
            'status' => true,
            'data' => $this->tournaments->tournamentState($tournament),
        ]);
    }

    public function enableScreenShare(Request $request, string $uuid): JsonResponse
    {
        $tournament = Tournament::query()->where('uuid', $uuid)->firstOrFail();
        $this->tournaments->ensureParticipant($tournament, $request->user()->id);

        TournamentParticipant::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $request->user()->id)
            ->update(['screen_share_enabled' => true]);

        return response()->json(['status' => true, 'message' => 'Screen sharing acknowledged.']);
    }

    public function submitRoundScore(Request $request, string $uuid, int $roundId): JsonResponse
    {
        $request->validate([
            'score' => 'required|integer|min:0',
            'time' => 'nullable|string',
            'left_early' => 'sometimes|boolean',
            'meta' => 'sometimes|array',
        ]);

        $tournament = Tournament::query()->where('uuid', $uuid)->firstOrFail();
        $this->tournaments->ensureParticipant($tournament, $request->user()->id);

        $round = TournamentRound::query()
            ->where('id', $roundId)
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        if ($round->status === 'ended') {
            throw ValidationException::withMessages(['round' => 'This round has already ended.']);
        }

        $rec = $this->tournaments->upsertRoundScore(
            $round,
            $request->user()->id,
            (int) $request->input('score'),
            $request->input('time'),
            array_merge($request->input('meta', []), [
                'left_early' => (bool) $request->input('left_early', false),
            ])
        );

        if ($request->boolean('left_early')) {
            $rec->update(['left_early' => true]);
        }

        return response()->json([
            'status' => true,
            'rank' => $rec->fresh()->rank,
            'leaderboard' => $round->scores()->orderBy('rank')->get(),
        ]);
    }

    /**
     * Mobile poll: auto-transition countdown -> started, and detect ended state.
     */
    public function syncRound(Request $request, string $uuid, int $roundId): JsonResponse
    {
        $tournament = Tournament::query()->where('uuid', $uuid)->firstOrFail();
        $this->tournaments->ensureParticipant($tournament, $request->user()->id);

        $round = TournamentRound::query()
            ->where('id', $roundId)
            ->where('tournament_id', $tournament->id)
            ->firstOrFail();

        if ($round->status === 'countdown' && $round->countdown_ends_at && Carbon::parse($round->countdown_ends_at)->isPast()) {
            $this->tournaments->markRoundStarted($round);
        }

        return response()->json([
            'status' => true,
            'round' => $round->fresh(),
        ]);
    }
}
