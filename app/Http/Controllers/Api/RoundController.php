<?php

namespace App\Http\Controllers\Api;

use App\Domain\rounds\RoundRankingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteRoundRequest;
use App\Http\Requests\StoreRoundRequest;
use App\Models\Round;
use App\Models\RoundPlayer;
use App\Models\RoundScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoundController extends Controller
{
    public function __construct(
        private RoundRankingService $rankingService
    ) {}

    /**
     * GET /api/rounds - List user's rounds
     */
    public function index(Request $request): JsonResponse
    {
        $query = Round::where('user_id', $request->user()->id)
            ->with(['players' => function ($query) {
                $query->where('is_me', true);
            }]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $rounds = $query->orderBy('played_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $data = $rounds->map(function (Round $round) {
            $myPlayer    = $round->players->first();
            $playerCount = RoundPlayer::where('round_id', $round->id)->count();

            return [
                'id'           => $round->id,
                'course_name'  => $round->course_name,
                'hole_count'   => $round->hole_count,
                'status'       => $round->status,
                'played_at'    => $round->played_at->format('Y-m-d'),
                'total_score'  => $myPlayer?->total_score ?? 0,
                'score_vs_par' => $myPlayer?->score_vs_par ?? 0,
                'rank'         => $myPlayer?->rank,
                'player_count' => $playerCount,
                'started_at'   => $round->started_at?->toIso8601String(),
                'completed_at' => $round->completed_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success'      => true,
            'data'         => $data,
            'current_page' => $rounds->currentPage(),
            'per_page'     => $rounds->perPage(),
            'total'        => $rounds->total(),
            'last_page'    => $rounds->lastPage(),
        ]);
    }

    /**
     * POST /api/rounds - Create new round
     */
    public function store(StoreRoundRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated) {
            // Create round
            $round = Round::create([
                'user_id'     => $request->user()->id,
                'course_id'   => $validated['course_id'] ?? null,
                'course_name' => $validated['course_name'],
                'hole_count'  => $validated['hole_count'],
                'hole_pars'   => $validated['hole_pars'],
                'status'      => 'in_progress',
                'memo'        => $validated['memo'] ?? null,
                'played_at'   => $validated['played_at'],
                'started_at'  => now(),
            ]);

            // Create players
            foreach ($validated['players'] as $index => $playerData) {
                $player = RoundPlayer::create([
                    'round_id'    => $round->id,
                    'user_id'     => $playerData['user_id'] ?? null,
                    'player_name' => $playerData['is_me']
                        ? $request->user()->name
                        : $playerData['name'],
                    'player_order' => $index + 1,
                    'is_me'        => $playerData['is_me'],
                ]);

                // Create empty scores for each hole
                foreach ($validated['hole_pars'] as $holeNumber => $par) {
                    RoundScore::create([
                        'round_id'        => $round->id,
                        'round_player_id' => $player->id,
                        'hole_number'     => $holeNumber + 1,
                        'par'             => $par,
                        'score'           => null,
                    ]);
                }
            }

            // Load relationships for response
            $round->load('players');

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => $round->id,
                    'user_id'      => $round->user_id,
                    'course_id'    => $round->course_id,
                    'course_name'  => $round->course_name,
                    'hole_count'   => $round->hole_count,
                    'hole_pars'    => $round->hole_pars,
                    'status'       => $round->status,
                    'memo'         => $round->memo,
                    'played_at'    => $round->played_at->format('Y-m-d'),
                    'started_at'   => $round->started_at->toIso8601String(),
                    'completed_at' => $round->completed_at?->toIso8601String(),
                    'players'      => $round->players->map(fn ($p) => [
                        'id'           => $p->id,
                        'player_name'  => $p->player_name,
                        'player_order' => $p->player_order,
                        'is_me'        => $p->is_me,
                        'user_id'      => $p->user_id,
                        'total_score'  => $p->total_score,
                        'score_vs_par' => $p->score_vs_par,
                        'rank'         => $p->rank,
                        'is_winner'    => $p->is_winner,
                    ]),
                ],
            ], 201);
        });
    }

    /**
     * GET /api/rounds/{id} - Show round details
     */
    public function show(Request $request, Round $round): JsonResponse
    {
        if ($round->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this round.',
            ], 403);
        }

        $round->load(['players.scores' => function ($query) {
            $query->orderBy('hole_number');
        }]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $round->id,
                'user_id'      => $round->user_id,
                'course_id'    => $round->course_id,
                'course_name'  => $round->course_name,
                'hole_count'   => $round->hole_count,
                'hole_pars'    => $round->hole_pars,
                'total_par'    => $round->total_par,
                'status'       => $round->status,
                'memo'         => $round->memo,
                'played_at'    => $round->played_at->format('Y-m-d'),
                'started_at'   => $round->started_at?->toIso8601String(),
                'completed_at' => $round->completed_at?->toIso8601String(),
                'players'      => $round->players->map(function ($player) {
                    return [
                        'id'           => $player->id,
                        'player_name'  => $player->player_name,
                        'player_order' => $player->player_order,
                        'is_me'        => $player->is_me,
                        'total_score'  => $player->total_score,
                        'score_vs_par' => $player->score_vs_par,
                        'rank'         => $player->rank,
                        'is_winner'    => $player->is_winner,
                        'scores'       => $player->scores->map(fn ($s) => [
                            'hole_number' => $s->hole_number,
                            'par'         => $s->par,
                            'score'       => $s->score,
                            'memo'        => $s->memo,
                        ]),
                    ];
                }),
            ],
        ]);
    }

    /**
     * POST /api/rounds/{id}/complete - Complete round with scores
     */
    public function complete(CompleteRoundRequest $request, Round $round): JsonResponse
    {
        if ($round->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this round.',
            ], 403);
        }

        if ($round->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Round is not in progress.',
            ], 409);
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($round, $validated) {
            // Update scores
            foreach ($validated['scores'] as $playerScores) {
                $player = RoundPlayer::findOrFail($playerScores['player_id']);

                if ($player->round_id !== $round->id) {
                    abort(422, 'Player does not belong to this round.');
                }

                foreach ($playerScores['hole_scores'] as $holeScore) {
                    $score = RoundScore::where('round_id', $round->id)
                        ->where('round_player_id', $player->id)
                        ->where('hole_number', $holeScore['hole_number'])
                        ->firstOrFail();

                    $score->update([
                        'score'       => $holeScore['score'],
                        'memo'        => $holeScore['memo'] ?? null,
                        'recorded_at' => now(),
                    ]);
                }
            }

            // Reload round with updated scores
            $round->load('players.scores');

            // Calculate player totals
            $this->rankingService->updatePlayerScores($round);

            // Calculate rankings
            $this->rankingService->calculateRankings($round);

            // Mark round as completed
            $round->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // Reload for response
            $round->refresh();
            $round->load(['players.scores' => function ($query) {
                $query->orderBy('hole_number');
            }]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'           => $round->id,
                    'course_name'  => $round->course_name,
                    'hole_count'   => $round->hole_count,
                    'hole_pars'    => $round->hole_pars,
                    'total_par'    => $round->total_par,
                    'played_at'    => $round->played_at->format('Y-m-d'),
                    'started_at'   => $round->started_at->toIso8601String(),
                    'completed_at' => $round->completed_at->toIso8601String(),
                    'players'      => $round->players->sortBy('rank')->values()->map(function ($player) {
                        return [
                            'player_id'    => $player->id,
                            'player_name'  => $player->player_name,
                            'is_me'        => $player->is_me,
                            'scores'       => $player->scores->pluck('score')->toArray(),
                            'total_score'  => $player->total_score,
                            'score_vs_par' => $player->score_vs_par,
                            'rank'         => $player->rank,
                            'is_winner'    => $player->is_winner,
                        ];
                    }),
                ],
            ]);
        });
    }

    /**
     * GET /api/rounds/{id}/scorecard - Get scorecard view
     */
    public function scorecard(Request $request, Round $round): JsonResponse
    {
        if ($round->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this round.',
            ], 403);
        }

        $round->load(['players.scores' => function ($query) {
            $query->orderBy('hole_number');
        }]);

        return response()->json([
            'success' => true,
            'data'    => [
                'round_id'    => $round->id,
                'course_name' => $round->course_name,
                'hole_count'  => $round->hole_count,
                'hole_pars'   => $round->hole_pars,
                'total_par'   => $round->total_par,
                'scorecards'  => $round->players->map(function ($player) {
                    return [
                        'player_id'   => $player->id,
                        'player_name' => $player->player_name,
                        'scores'      => $player->scores->map(fn ($s) => [
                            'hole_number' => $s->hole_number,
                            'par'         => $s->par,
                            'score'       => $s->score,
                            'memo'        => $s->memo,
                        ]),
                        'total_score'  => $player->total_score,
                        'score_vs_par' => $player->score_vs_par,
                    ];
                }),
            ],
        ]);
    }

    /**
     * DELETE /api/rounds/{id} - Cancel round
     */
    public function destroy(Request $request, Round $round): JsonResponse
    {
        if ($round->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this round.',
            ], 403);
        }

        if ($round->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed round.',
            ], 409);
        }

        $round->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => '라운드가 취소되었습니다.',
        ]);
    }
}
