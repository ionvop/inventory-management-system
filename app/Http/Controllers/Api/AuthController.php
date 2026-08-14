<?php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use App\Models\User;

class AuthController extends Controller
{
    public function users()
    {
        return $this->data(User::orderBy('username')->get(['id', 'username']));
    }
}