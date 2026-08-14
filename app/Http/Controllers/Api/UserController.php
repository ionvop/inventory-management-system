<?php
// app/Http/Controllers/Api/UserController.php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where('username', 'like', "%{$search}%");
        }

        $sort = $request->query('sort', 'username');
        $order = $request->query('order', 'asc');
        $query->orderBy(in_array($sort, ['username', 'time']) ? $sort : 'username', $order);

        return $this->paginated($query->paginate($request->query('limit', 25)));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated() + ['time' => now()->timestamp]);
        return $this->data($user, 201);
    }

    public function show(User $user)
    {
        return $this->data($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return $this->data($user);
    }

    public function destroy(User $user)
    {
        if ($user->transactions()->exists()) {
            throw new ApiException('USER_HAS_TRANSACTIONS', 'This user has existing transactions and cannot be deleted.', 409);
        }

        $user->delete();
        return response()->noContent();
    }
}