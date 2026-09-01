<?php
// app/Http/Controllers/Api/DashboardController.php
namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $items = Item::withStock()->get();
        $lowStock = $items->filter(fn ($i) => $i->is_low_stock)->values();

        $timezone = $this->resolveTimezone($request);
        $todayStart = Carbon::parse('today', $timezone)->startOfDay()->utc();
        $todayEnd = Carbon::parse('today', $timezone)->endOfDay()->utc();

        return $this->data([
            'total_items' => $items->count(),
            'low_stock_count' => $lowStock->count(),
            'low_stock_items' => $lowStock->take(10)->values(),
            'today_transactions' => [
                'in_count' => Transaction::whereBetween('posted_at', [$todayStart, $todayEnd])->where('movement', 'in')->count(),
                'out_count' => Transaction::whereBetween('posted_at', [$todayStart, $todayEnd])->where('movement', 'out')->count(),
            ],
            'recent_transactions' => Transaction::with(['item:id,name,unit', 'user:id,username'])
                ->orderByDesc('created_at')->take(10)->get(),
        ]);
    }
}