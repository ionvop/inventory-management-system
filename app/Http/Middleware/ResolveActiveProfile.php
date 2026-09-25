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
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profileId = Session::get(static::SESSION_KEY);

        if (! is_int($profileId) && ! is_string($profileId)) {
            return Redirect::to('/');
        }

        $profile = Profile::query()->findSole($profileId);

        $request->attributes->set(static::ATTRIBUTE, $profile);

        return $next($request);
    }
}
