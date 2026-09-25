<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * The session key that stores the currently selected profile id.
     */
    protected const string SESSION_KEY = 'active_profile_id';

    /**
     * Display the profile picker.
     */
    public function index(): Response
    {
        $profiles = Profile::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('ProfilePicker', [
            'profiles' => $profiles->map(fn ($profile) => [
                'id' => $profile->id,
                'name' => $profile->name,
                'role' => $profile->role,
            ]),
        ]);
    }

    /**
     * Select a profile to start a session attributed to it.
     */
    public function select(int $id): RedirectResponse
    {
        $profile = Profile::query()->findSole($id);

        Session::put(static::SESSION_KEY, $profile->id);

        return Redirect::to('/dashboard');
    }

    /**
     * Create a new profile.
     */
    public function store(): RedirectResponse
    {
        $data = Request::all();

        $data = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'role' => 'required|in:staff,supervisor,administrator',
        ]);

        Profile::create($data);

        return Redirect::back();
    }

    /**
     * Update an existing profile.
     */
    public function update(int $id): RedirectResponse
    {
        $profile = Profile::query()->findSole($id);

        $data = Request::all();

        $data = Validator::validate($data, [
            'name' => 'required|string|max:255',
            'role' => 'required|in:staff,supervisor,administrator',
        ]);

        $profile->update($data);

        return Redirect::back();
    }

    /**
     * Soft-delete a profile.
     */
    public function destroy(int $id): RedirectResponse
    {
        $profile = Profile::query()->findSole($id);

        $profile->delete();

        return Redirect::back();
    }

    /**
     * Clear the active profile and return to the picker.
     */
    public function logout(): RedirectResponse
    {
        Session::forget(static::SESSION_KEY);

        return Redirect::to('/');
    }
}
