<?php

namespace App\Domain\rounds;

use App\Models\Round;
use App\Models\RoundPlayer;

class RoundRankingService
{
    /**
     * Calculate rankings for all players in a round based on total score
     * and back-nine tiebreaker rules.
     */
    public function calculateRankings(Round $round): void
    {
        $players = $round->players()->with('scores')->get();

        // Sort players by total score ascending, then apply tiebreaker
        $sortedPlayers = $players->sortBy(function (RoundPlayer $player) use ($round) {
            return [
                $player->total_score,
                -$this->calculateTiebreakerScore($player, $round),
            ];
        })->values();

        // Assign ranks with ties
        $currentRank        = 1;
        $previousScore      = null;
        $previousTiebreaker = null;

        foreach ($sortedPlayers as $index => $player) {
            $tiebreaker = $this->calculateTiebreakerScore($player, $round);

            if ($previousScore !== null &&
                ($player->total_score !== $previousScore || $tiebreaker !== $previousTiebreaker)) {
                $currentRank = $index + 1;
            }

            $player->rank = $currentRank;
            $player->save();

            $previousScore      = $player->total_score;
            $previousTiebreaker = $tiebreaker;
        }

        // Mark winners (rank 1)
        $round->players()->update(['is_winner' => false]);
        $round->players()->where('rank', 1)->update(['is_winner' => true]);
    }

    /**
     * Calculate tiebreaker score (sum of back-nine scores, higher is better).
     * For 9-hole: holes 9,8,7,6,5...
     * For 18-hole: holes 18,17,16,15,14,13,12,11,10
     */
    private function calculateTiebreakerScore(RoundPlayer $player, Round $round): int
    {
        $scores = $player->scores()
            ->orderBy('hole_number', 'desc')
            ->pluck('score', 'hole_number');

        $backNineHoles = $round->hole_count === 18
            ? range(18, 10)  // holes 18-10 for 18-hole
            : range(9, 1);    // holes 9-1 for 9-hole

        $tiebreakerScore = 0;
        foreach ($backNineHoles as $holeNumber) {
            if (isset($scores[$holeNumber])) {
                $tiebreakerScore += $scores[$holeNumber];
            }
        }

        return $tiebreakerScore;
    }

    /**
     * Update player scores and calculate score vs par.
     */
    public function updatePlayerScores(Round $round): void
    {
        foreach ($round->players as $player) {
            $totalScore = $player->scores()->sum('score');
            $scoreVsPar = $totalScore - $round->total_par;

            $player->update([
                'total_score'  => $totalScore,
                'score_vs_par' => $scoreVsPar,
            ]);
        }
    }
}
