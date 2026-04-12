<?php

namespace App\Http\Controllers;

use App\Http\Services\TournamentService;
use App\Models\Tournament;
use App\Models\TournamentRound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournaments
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $t = Tournament::query()->create([
            'title' => $request->input('title', 'Whipple Tournament'),
            'status' => 'draft',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'tournament' => $t]);
    }

    public function import(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'from_most_frequent' => 'required|integer|min:0|max:500',
            'from_most_wins' => 'required|integer|min:0|max:500',
        ]);

        $t = Tournament::query()->findOrFail($id);
        $this->tournaments->importFromLeaderboards(
            $t,
            (int) $request->input('from_most_frequent'),
            (int) $request->input('from_most_wins'),
            $request->user()->id
        );

        return response()->json(['status' => true, 'message' => 'Players imported.']);
    }

    public function addPlayer(Request $request, int $id): JsonResponse
    {
        $request->validate(['identifier' => 'required|string']);

        $t = Tournament::query()->findOrFail($id);
        $p = $this->tournaments->addParticipantByIdentifier($t, $request->input('identifier'));

        return response()->json(['status' => true, 'participant' => $p->load('user:id,name,email')]);
    }

    public function removePlayer(Request $request, int $id, int $userId): JsonResponse
    {
        $t = Tournament::query()->findOrFail($id);
        $this->tournaments->removeParticipant($t, $userId);

        return response()->json(['status' => true]);
    }

    public function createRound(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'game_type' => 'required|string|in:quiz,tap_rush,math_clash,color_switch,defuse_x',
            'round_number' => 'nullable|integer|min:1',
        ]);

        $t = Tournament::query()->findOrFail($id);
        $round = $this->tournaments->createRound(
            $t,
            $request->input('game_type'),
            (int) $request->input('round_number', $t->rounds()->max('round_number') + 1)
        );

        return response()->json(['status' => true, 'round' => $round]);
    }

    public function startCountdown(Request $request, int $roundId): JsonResponse
    {
        $round = TournamentRound::query()->findOrFail($roundId);
        $this->tournaments->startRoundCountdown($round);

        return response()->json(['status' => true, 'round' => $round->fresh()]);
    }

    public function endRound(Request $request, int $roundId): JsonResponse
    {
        $round = TournamentRound::query()->findOrFail($roundId);
        $this->tournaments->markRoundEnded($round);

        return response()->json(['status' => true, 'round' => $round->fresh()]);
    }

    public function eliminate(Request $request, int $id): JsonResponse
    {
        $request->validate(['bottom_count' => 'required|integer|min:1|max:500']);

        $t = Tournament::query()->findOrFail($id);
        $n = $this->tournaments->eliminateBottom($t, (int) $request->input('bottom_count'));

        return response()->json(['status' => true, 'eliminated' => $n]);
    }

    public function commentary(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:5000',
            'key_moment' => 'sometimes|boolean',
        ]);

        $t = Tournament::query()->findOrFail($id);
        $c = $this->tournaments->addCommentary(
            $t,
            $request->user()->id,
            $request->input('body'),
            $request->boolean('key_moment')
        );

        return response()->json(['status' => true, 'commentary' => $c]);
    }

    public function screenShare(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'slot' => 'required|integer|in:1,2',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $t = Tournament::query()->findOrFail($id);
        $s = $this->tournaments->setScreenShareSlot(
            $t,
            (int) $request->input('slot'),
            (int) $request->input('user_id')
        );

        return response()->json(['status' => true, 'screen_share' => $s]);
    }

    public function reset(Request $request, int $id): JsonResponse
    {
        $t = Tournament::query()->findOrFail($id);
        $this->tournaments->resetTournament($t);

        return response()->json(['status' => true, 'message' => 'Tournament reset.']);
    }

    public function state(Request $request, int $id): JsonResponse
    {
        $t = Tournament::query()->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $this->tournaments->tournamentState($t),
        ]);
    }
}
