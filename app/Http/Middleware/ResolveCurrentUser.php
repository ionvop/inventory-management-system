<?php
// app/Http/Middleware/ResolveCurrentUser.php
namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');

        if (! $userId) {
            return response()->json([
                'error' => [
                    'code' => 'MISSING_USER_ID',
                    'message' => 'The X-User-Id header is required.',
                    'details' => null,
                ],
            ], 400);
        }

        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => "No user found with id {$userId}.",
                    'details' => null,
                ],
            ], 404);
        }

        // Lets $request->user() work naturally in controllers/policies.
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}