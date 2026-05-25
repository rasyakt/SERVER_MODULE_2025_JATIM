<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBlocked
{
    




    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_blocked) {
            return response()->json([
                'status' => 'blocked',
                'message' => 'User blocked',
                'reason' => $user->block_reason ?? 'You have been blocked by an administrator'
            ], 403);
        }

        return $next($request);
    }
}
