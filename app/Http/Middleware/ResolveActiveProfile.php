<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the profile selected on the profile picker for the current request.
 *
 * The application is passwordless (FR-1.1): the acting identity is the profile
 * id stored in the session by ProfileController. Routes that need an acting
 * profile are wrapped in this middleware, which redirects back to the picker
 * when no valid profile is selected.
 */
class ResolveActiveProfile
{
    /**
     * The session key that stores the currently selected profile id.
     */
    public const string SESSION_KEY = 'active_profile_id';

    /**
     * The request attribute the resolved profile is stored on.
     */
    public const string ATTRIBUTE = 'active_profile';

    /**
     * Resolve a profile id to a profile, or null when it is missing.
     *
     * The id comes from the session and may be stale: the profile could have
     * been soft-deleted, hard-deleted, or the value could be malformed. Unlike
     * findSole(), find() returns null instead of throwing, so a stale id is
     * treated as "no active profile" rather than surfacing as a 404 or 500.
     */
    public static function resolve(mixed $id): ?Profile
    {
        if (! is_int($id) && ! is_string($id)) {
            return null;
        }

        return Profile::query()->find($id);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profile = static::resolve(Session::get(static::SESSION_KEY));

        if (! $profile instanceof Profile) {
            return Redirect::to('/');
        }

        $request->attributes->set(static::ATTRIBUTE, $profile);

        return $next($request);
    }
}
