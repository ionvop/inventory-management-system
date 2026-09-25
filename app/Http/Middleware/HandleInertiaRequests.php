<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'activeProfile' => fn (): ?array => $this->activeProfile(),
        ];
    }

    /**
     * Resolve the profile selected on the profile picker, if any.
     *
     * @return array{id: int, name: string, role: string}|null
     */
    protected function activeProfile(): ?array
    {
        $profileId = Session::get(ResolveActiveProfile::SESSION_KEY);

        if (! is_int($profileId) && ! is_string($profileId)) {
            return null;
        }

        $profile = Profile::query()->findSole($profileId);

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'role' => $profile->role,
        ];
    }
}
