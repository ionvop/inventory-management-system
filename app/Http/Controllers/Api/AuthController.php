<?php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function users()
    {
        return $this->data(User::orderBy('username')->get(['id', 'username']));
    }

    /**
     * Bootstrap the first user when the app ships with empty data.
     * Only allowed while no users exist yet.
     */
    public function store(StoreUserRequest $request)
    {
        if (User::exists()) {
            throw new ApiException(
                'USERS_EXIST',
                'Users already exist. Create additional users from the settings page.',
                409,
            );
        }

        $user = User::create($request->validated());
        return $this->data($user, 201);
    }
}