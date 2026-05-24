<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Get all platform users (Admin Only).
     */
    public function index()
    {
        $users = User::where('role', '!=', 'admin')->get();

        return response()->json([
            'totalElements' => $users->count(),
            'content' => $users->map(function($user) {
                return [
                    'username' => $user->username,
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
                    'updated_at' => $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : '',
                ];
            })
        ], 200);
    }

    /**
     * Get all admins list (Admin Only).
     */
    public function getAdmins()
    {
        $admins = User::where('role', 'admin')->get();

        return response()->json([
            'totalElements' => $admins->count(),
            'content' => $admins->map(function($admin) {
                return [
                    'username' => $admin->username,
                    'last_login_at' => $admin->last_login_at ? $admin->last_login_at->format('Y-m-d H:i:s') : '',
                    'created_at' => $admin->created_at ? $admin->created_at->format('Y-m-d H:i:s') : '',
                    'updated_at' => $admin->updated_at ? $admin->updated_at->format('Y-m-d H:i:s') : '',
                ];
            })
        ], 200);
    }

    /**
     * Create a user (Admin Only).
     */
    public function store(Request $request)
    {
        // 1. Standard validations (Bootstrap app handles standard formatting)
        $validator = Validator::make($request->all(), [
            'username' => 'required|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // 2. Uniqueness check for dynamic JATIM format
        if (User::where('username', $request->username)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Username already exists'
            ], 400);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'player', // Default role
        ]);

        return response()->json([
            'status' => 'success',
            'username' => $user->username
        ], 201);
    }

    /**
     * Update a user (Admin Only).
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 403); // Specific 403 for not found!
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Check if new username is taken
        if (User::where('username', $request->username)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Username already exists'
            ], 400);
        }

        $user->update([
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'username' => $user->username
        ], 201); // PUT returns 201
    }

    /**
     * Delete a user (Admin Only).
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 403); // Specific 403 for not found!
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * Get user profile details by username.
     */
    public function show(Request $request, $username)
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        $currentUser = $request->user();
        $isSelf = $currentUser && $currentUser->id === $user->id;

        // Authored Games list logic
        $gamesQuery = $user->authoredGames();
        if (!$isSelf) {
            // Only games with at least one version
            $gamesQuery->whereHas('versions');
        }

        $games = $gamesQuery->get();

        $authoredGames = [];
        foreach ($games as $game) {
            $authoredGames[] = [
                'slug' => $game->slug,
                'title' => $game->title,
                'description' => $game->description,
            ];
        }

        // Highscores list logic (highest score per game, sorted alphabetically by game title)
        $scores = Score::where('user_id', $user->id)
            ->with('game')
            ->get();

        $highestScores = $scores->groupBy('game_id')->map(function($gameScores) {
            return $gameScores->sortByDesc('score')->first();
        });

        $highscores = [];
        foreach ($highestScores as $scoreRecord) {
            if ($scoreRecord && $scoreRecord->game) {
                $highscores[] = [
                    'game' => [
                        'slug' => $scoreRecord->game->slug,
                        'title' => $scoreRecord->game->title,
                        'description' => $scoreRecord->game->description,
                    ],
                    'score' => (int) $scoreRecord->score,
                    'timestamp' => $scoreRecord->created_at ? $scoreRecord->created_at->format('Y-m-d\TH:i:s.000\Z') : '',
                ];
            }
        }

        // Sort alphabetically by game title
        usort($highscores, function($a, $b) {
            return strcmp($a['game']['title'], $b['game']['title']);
        });

        return response()->json([
            'username' => $user->username,
            'registeredTimestamp' => $user->created_at ? $user->created_at->format('Y-m-d\TH:i:s.000\Z') : '',
            'authoredGames' => $authoredGames,
            'highscores' => $highscores,
        ], 200);
    }
}
