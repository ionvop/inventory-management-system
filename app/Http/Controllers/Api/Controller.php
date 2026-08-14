<?php
// app/Http/Controllers/Api/Controller.php
namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller extends BaseController
{
    protected function data($payload, int $status = 200)
    {
        return response()->json(['data' => $payload], $status);
    }

    protected function paginated(LengthAwarePaginator $paginator)
    {
        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }
}