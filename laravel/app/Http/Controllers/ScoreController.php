<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScoreController extends Controller
{
    /**
     * Get highest scores of each player for a game (sorted by score descending).
     */
    public function index($slug)
    {
        $game = Game::where('slug', $slug)->first();

        if (!$game) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        $scores = Score::where('game_id', $game->id)
            ->with('user')
            ->get();

        // Get only the highest score for each unique player (user_id)
        $userHighestScores = $scores->groupBy('user_id')->map(function($userScores) {
            return $userScores->sortByDesc('score')->first();
        });

        // Sort all players highest scores by score descending
        $sortedScores = $userHighestScores->sortByDesc('score')->values();

        $scoresData = [];
        foreach ($sortedScores as $scoreRecord) {
            if ($scoreRecord && $scoreRecord->user) {
                $scoresData[] = [
                    'username' => $scoreRecord->user->username,
                    'score' => (int) $scoreRecord->score,
                    'timestamp' => $scoreRecord->created_at ? $scoreRecord->created_at->format('Y-m-d\TH:i:s.000\Z') : '',
                ];
            }
        }

        return response()->json([
            'scores' => $scoresData
        ], 200);
    }

    /**
     * Post a new score for the authenticated player.
     */
    public function store(Request $request, $slug)
    {
        $game = Game::where('slug', $slug)->first();

        if (!$game) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'score' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // The game version associated to the score is the latest one available
        $latestVersion = $game->versions()->orderBy('version', 'desc')->first();

        Score::create([
            'user_id' => $request->user()->id,
            'game_id' => $game->id,
            'game_version_id' => $latestVersion ? $latestVersion->id : null,
            'score' => $request->score,
        ]);

        return response()->json([
            'status' => 'success'
        ], 201);
    }
}
