<?php
// app/Http/Controllers/Api/DashboardController.php
namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function summary()
    {
        $items = Item::withStock()->get();
        $lowStock = $items->filter(fn ($i) => $i->is_low_stock)->values();

        $todayStart = now()->startOfDay()->timestamp;
        $todayEnd = now()->endOfDay()->timestamp;

        return $this->data([
            'total_items' => $items->count(),
            'low_stock_count' => $lowStock->count(),
            'low_stock_items' => $lowStock->take(10)->values(),
            'today_transactions' => [
                'in_count' => Transaction::whereBetween('posted_time', [$todayStart, $todayEnd])->where('movement', 'in')->count(),
                'out_count' => Transaction::whereBetween('posted_time', [$todayStart, $todayEnd])->where('movement', 'out')->count(),
            ],
            'recent_transactions' => Transaction::with(['item:id,name', 'user:id,username'])
                ->orderByDesc('time')->take(10)->get(),
        ]);
    }
}