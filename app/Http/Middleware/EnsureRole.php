<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to profiles holding one of the allowed roles.
 *
 * Role is attached to the profile rather than to a separate credential
 * (Section 2.3), so this gate reads the profile resolved by
 * ResolveActiveProfile. Catalog management is limited to administrators
 * (FR-2.1, FR-2.2).
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$roles  The roles allowed to access the route.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $profile = $request->attributes->get(ResolveActiveProfile::ATTRIBUTE);

        if (! $profile instanceof Profile) {
            $profileId = Session::get(ResolveActiveProfile::SESSION_KEY);

            $profile = is_int($profileId) || is_string($profileId)
                ? Profile::query()->findSole($profileId)
                : null;
        }

        abort_unless(
            $profile instanceof Profile && in_array($profile->role, $roles, true),
            403,
        );

        return $next($request);
    }
}
