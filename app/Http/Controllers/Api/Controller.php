<?php
// app/Http/Controllers/Api/Controller.php
namespace App\Http\Controllers\Api;

use DateTimeZone;
use Illuminate\Http\Request;
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

    /**
     * Resolve the timezone to use for a request. Prefers the browser's
     * IANA identifier sent via the X-Timezone header, falling back to the
     * app's configured timezone when absent or invalid. The header is
     * client-controlled, so it is validated against the full IANA list to
     * avoid a fatal Carbon exception on an unknown value.
     */
    protected function resolveTimezone(Request $request): string
    {
        $timezone = $request->header('X-Timezone');

        if ($timezone && in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            return $timezone;
        }

        return config('app.timezone');
    }
}