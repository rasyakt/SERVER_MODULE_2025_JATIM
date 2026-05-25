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
    


    public function index()
    {
        $users = User::where('role', '!=', 'admin')->get();

        return response()->json([
            'totalElements' => $users->count(),
            'content' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'is_blocked' => $user->is_blocked,
                    'block_reason' => $user->block_reason,
                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
                    'updated_at' => $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : '',
                ];
            })
        ], 200);
    }

    


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

    


    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'username' => 'required|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        
        if (User::where('username', $request->username)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Username already exists'
            ], 400);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'player', 
        ]);

        return response()->json([
            'status' => 'success',
            'username' => $user->username
        ], 201);
    }

    


    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 403); 
        }

        
        if ($request->has('is_blocked')) {
            $user->update([
                'is_blocked' => (bool) $request->is_blocked,
                'block_reason' => $request->block_reason,
            ]);
            return response()->json([
                'status' => 'success',
                'username' => $user->username
            ], 201);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|min:4|max:60',
            'password' => 'required|min:5|max:10',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        
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
        ], 201); 
    }

    


    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 403); 
        }

        $user->delete();

        return response()->noContent();
    }

    


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

        
        $gamesQuery = $user->authoredGames();
        if (!$isSelf) {
            
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
