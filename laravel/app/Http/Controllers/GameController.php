<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use ZipArchive;

class GameController extends Controller
{
    /**
     * Get a paginated, sorted list of games (having versions).
     */
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $size = (int) $request->query('size', 10);
        $sortBy = $request->query('sortBy', 'title');
        $sortDir = $request->query('sortDir', 'asc');

        if ($size < 1) {
            $size = 10;
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        // Must only include games that have at least one version
        $query = Game::has('versions')->with(['author', 'versions', 'scores']);

        // Apply sorting
        if ($sortBy === 'title') {
            $query->orderBy('title', $sortDir);
        } elseif ($sortBy === 'popular') {
            // Sort by total score records count
            $query->withCount('scores')->orderBy('scores_count', $sortDir);
        } elseif ($sortBy === 'uploaddate') {
            // Sort by latest version creation timestamp
            $query->select('games.*')
                ->join('game_versions as gv', function($join) {
                    $join->on('games.id', '=', 'gv.game_id')
                        ->whereRaw('gv.id = (select max(id) from game_versions where game_versions.game_id = games.id)');
                })
                ->orderBy('gv.created_at', $sortDir);
        }

        $totalElements = $query->count();
        $games = $query->offset($page * $size)->limit($size)->get();

        $content = $games->map(function($game) {
            $latestVer = $game->versions->sortByDesc('id')->first();
            return [
                'slug' => $game->slug,
                'title' => $game->title,
                'description' => $game->description,
                'thumbnail' => $latestVer && $latestVer->thumbnail ? $latestVer->thumbnail : null,
                'uploadTimestamp' => $latestVer && $latestVer->created_at ? $latestVer->created_at->format('Y-m-d\TH:i:s.000\Z') : '',
                'author' => $game->author->username ?? '',
                'scoreCount' => $game->scores->count(),
            ];
        });

        return response()->json([
            'page' => $page,
            'size' => $size,
            'totalElements' => $totalElements,
            'content' => $content,
        ], 200);
    }

    /**
     * Create a new game (Developer/User).
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'dev') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Only developers are allowed to create games'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:60',
            'description' => 'required|max:200',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $slug = Str::slug($request->title);

        // Check if slug is unique
        if (Game::where('slug', $slug)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'slug' => 'Game title already exists'
            ], 400);
        }

        $game = Game::create([
            'slug' => $slug,
            'title' => $request->title,
            'description' => $request->description,
            'author_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'slug' => $game->slug
        ], 201);
    }

    /**
     * Get details of a single game by slug.
     */
    public function show($slug)
    {
        $game = Game::where('slug', $slug)->with(['author', 'versions', 'scores'])->first();

        if (!$game) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        $latestVer = $game->versions->sortByDesc('id')->first();
        $versionNumber = $latestVer ? $latestVer->version : 1;

        return response()->json([
            'slug' => $game->slug,
            'title' => $game->title,
            'description' => $game->description,
            'thumbnail' => $latestVer && $latestVer->thumbnail ? $latestVer->thumbnail : null,
            'uploadTimestamp' => $latestVer && $latestVer->created_at ? $latestVer->created_at->format('Y-m-d\TH:i:s.000\Z') : '',
            'author' => $game->author->username ?? '',
            'scoreCount' => $game->scores->count(),
            'gamePath' => "/games/{$game->slug}/{$versionNumber}/",
        ], 200);
    }

    /**
     * Update title and description (Author Only).
     */
    public function update(Request $request, $slug)
    {
        if ($request->user()->role !== 'dev') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Only developers are allowed to update games'
            ], 403);
        }

        $game = Game::where('slug', $slug)->first();

        if (!$game) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        if ($game->author_id !== $request->user()->id) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the game author'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|min:3|max:60',
            'description' => 'required|max:200',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $game->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success'
        ], 200);
    }

    /**
     * Delete a game, versions, and scores (Author Only).
     */
    public function destroy(Request $request, $slug)
    {
        if ($request->user()->role !== 'dev') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Only developers are allowed to delete games'
            ], 403);
        }

        $game = Game::where('slug', $slug)->first();

        if (!$game) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'Not found'
            ], 404);
        }

        if ($game->author_id !== $request->user()->id) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the game author'
            ], 403);
        }

        $game->delete();

        return response()->noContent();
    }

    /**
     * Upload game files (Non-REST, Zip upload, Token in parameter or header).
     */
    public function upload(Request $request, $slug)
    {
        // 1. Authenticate manually using token form/query parameter or Bearer header
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response('Missing token', 401)->header('Content-Type', 'text/plain');
        }

        $pat = PersonalAccessToken::findToken($token);
        if (!$pat || !$pat->tokenable) {
            return response('Invalid token', 401)->header('Content-Type', 'text/plain');
        }

        $user = $pat->tokenable;

        if ($user->role !== 'dev') {
            return response('Only developers are allowed to upload games', 403)->header('Content-Type', 'text/plain');
        }

        if ($user->is_blocked) {
            return response('User blocked: ' . ($user->block_reason ?? 'You have been blocked by an administrator'), 403)->header('Content-Type', 'text/plain');
        }

        // 2. Find game
        $game = Game::where('slug', $slug)->first();
        if (!$game) {
            return response('Game not found', 404)->header('Content-Type', 'text/plain');
        }

        // 3. Check author
        if ($game->author_id !== $user->id) {
            return response('User is not author of the game', 403)->header('Content-Type', 'text/plain');
        }

        // 4. Validate zipfile
        if (!$request->hasFile('zipfile') || !$request->file('zipfile')->isValid()) {
            return response('No zip file provided', 400)->header('Content-Type', 'text/plain');
        }

        $file = $request->file('zipfile');

        // 5. Versioning
        $latestVersion = $game->versions()->max('version') ?? 0;
        $newVersion = $latestVersion + 1;

        // 6. Extract Zip file
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) === true) {
            $destPath = public_path("games/{$slug}/{$newVersion}");
            
            // Create destination folder if not exists
            if (!file_exists($destPath)) {
                mkdir($destPath, 0777, true);
            }

            $zip->extractTo($destPath);
            $zip->close();
        } else {
            return response('Could not open ZIP file', 400)->header('Content-Type', 'text/plain');
        }

        // 7. Check for thumbnail.png
        $hasThumbnail = file_exists($destPath . '/thumbnail.png');
        $thumbnailUrl = $hasThumbnail ? "/games/{$slug}/{$newVersion}/thumbnail.png" : null;

        // 8. Create GameVersion record
        $gameVersion = GameVersion::create([
            'game_id' => $game->id,
            'version' => $newVersion,
            'path' => "/games/{$slug}/{$newVersion}/",
            'thumbnail' => $thumbnailUrl,
        ]);

        return response()->json([
            'status' => 'success',
            'version' => $newVersion,
            'thumbnail' => $thumbnailUrl,
        ], 200);
    }
}
