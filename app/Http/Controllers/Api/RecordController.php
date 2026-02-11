<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Round;
use App\Models\RoundPlayer;
use App\Models\RoundScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    /**
     * GET /api/records/statistics - Get user statistics summary
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $year   = $request->input('year', now()->year);
        $month  = $request->input('month');

        // Build query for rounds
        $query = Round::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereYear('played_at', $year);

        if ($month) {
            $query->whereMonth('played_at', $month);
        }

        $roundIds = $query->pluck('id');

        // Get basic statistics
        $myPlayers = RoundPlayer::whereIn('round_id', $roundIds)
            ->where('is_me', true)
            ->get();

        $totalRounds  = $myPlayers->count();
        $averageScore = $totalRounds > 0 ? round($myPlayers->avg('total_score'), 1) : 0;
        $bestScore    = $totalRounds > 0 ? $myPlayers->min('total_score') : null;
        $worstScore   = $totalRounds > 0 ? $myPlayers->max('total_score') : null;
        $winCount     = $myPlayers->where('rank', 1)->count();

        // Get total holes played
        $totalHoles = Round::whereIn('id', $roundIds)->sum('hole_count');

        // Get par distribution
        $playerIds = $myPlayers->pluck('id');
        $scores    = RoundScore::whereIn('round_player_id', $playerIds)
            ->whereNotNull('score')
            ->get();

        $parDistribution = [
            'eagle_or_better' => $scores->filter(fn ($s) => ($s->score - $s->par) <= -2)->count(),
            'birdie'          => $scores->filter(fn ($s) => ($s->score - $s->par) === -1)->count(),
            'par'             => $scores->filter(fn ($s) => ($s->score - $s->par) === 0)->count(),
            'bogey'           => $scores->filter(fn ($s) => ($s->score - $s->par) === 1)->count(),
            'double_or_worse' => $scores->filter(fn ($s) => ($s->score - $s->par) >= 2)->count(),
        ];

        $period = $month ? sprintf('%d-%02d', $year, $month) : (string) $year;

        return response()->json([
            'success' => true,
            'data'    => [
                'period'             => $period,
                'total_rounds'       => $totalRounds,
                'average_score'      => $averageScore,
                'best_score'         => $bestScore,
                'worst_score'        => $worstScore,
                'total_holes_played' => $totalHoles,
                'win_count'          => $winCount,
                'par_distribution'   => $parDistribution,
            ],
        ]);
    }

    /**
     * GET /api/records/rounds - Get user round records
     */
    public function rounds(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $query = Round::where('user_id', $userId)
            ->where('status', 'completed')
            ->with(['players' => function ($q) {
                $q->where('is_me', true);
            }]);

        // Apply filters
        if ($request->has('year')) {
            $query->whereYear('played_at', $request->year);
        }

        if ($request->has('month')) {
            $query->whereMonth('played_at', $request->month);
        }

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $rounds = $query->orderBy('played_at', 'desc')
            ->paginate($request->get('per_page', 20));

        $data = $rounds->map(function (Round $round) {
            $myPlayer    = $round->players->first();
            $playerCount = RoundPlayer::where('round_id', $round->id)->count();

            return [
                'id'           => $round->id,
                'date'         => $round->played_at->format('Y-m-d'),
                'course_id'    => $round->course_id,
                'course_name'  => $round->course_name,
                'hole_count'   => $round->hole_count,
                'total_score'  => $myPlayer?->total_score ?? 0,
                'score_vs_par' => $myPlayer?->score_vs_par ?? 0,
                'rank'         => $myPlayer?->rank,
                'player_count' => $playerCount,
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
     * GET /api/records/monthly - Get monthly summary for a year
     */
    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $userId = $request->user()->id;
        $year   = $request->year;

        // Get monthly aggregated data
        $monthlySummary = Round::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereYear('played_at', $year)
            ->join('round_players', function ($join) {
                $join->on('rounds.id', '=', 'round_players.round_id')
                    ->where('round_players.is_me', true);
            })
            ->select(
                DB::raw('YEAR(played_at) as year'),
                DB::raw('MONTH(played_at) as month'),
                DB::raw('COUNT(rounds.id) as rounds_count'),
                DB::raw('ROUND(AVG(round_players.total_score), 1) as avg_score'),
                DB::raw('MIN(round_players.total_score) as best_score')
            )
            ->groupBy('year', 'month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $monthlySummary->map(function ($summary) {
                return [
                    'year'         => $summary->year,
                    'month'        => $summary->month,
                    'rounds_count' => $summary->rounds_count,
                    'avg_score'    => (float) $summary->avg_score,
                    'best_score'   => $summary->best_score,
                ];
            }),
        ]);
    }
}
