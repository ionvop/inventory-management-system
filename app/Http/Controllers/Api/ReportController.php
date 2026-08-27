<?php
// app/Http/Controllers/Api/ReportController.php
namespace App\Http\Controllers\Api;

use App\Models\Item;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function inventory(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $format = $request->query('format', 'json');

        $items = Item::withStock()
            ->with(['transactions' => function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, fn ($q, $v) => $q->where('posted_at', '>=', Carbon::parse($v)->startOfDay()))
                  ->when($dateTo, fn ($q, $v) => $q->where('posted_at', '<=', Carbon::parse($v)->endOfDay()))
                  ->with('user:id,username')
                  ->orderBy('posted_at');
            }])
            ->get();

        $payload = [
            'generated_time' => now()->toIso8601String(),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'items' => $items,
        ];

        return match ($format) {
            'csv' => $this->csv($items),
            'pdf' => Pdf::loadView('reports.inventory', [
                'items' => $items,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ])->download('inventory-report-'.now()->format('Y-m-d').'.pdf'),
            default => $this->data($payload),
        };
    }

    private function csv($items): StreamedResponse
    {
        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Unit', 'Current Stock', 'Minimum Stock', 'Transaction Date', 'Movement', 'Quantity', 'User']);
            foreach ($items as $item) {
                if ($item->transactions->isEmpty()) {
                    fputcsv($out, [$item->name, $item->unit, $item->current_stock, $item->minimum_stock, '', '', '', '']);
                }
                foreach ($item->transactions as $t) {
                    fputcsv($out, [$item->name, $item->unit, $item->current_stock, $item->minimum_stock, $t->posted_at->format('Y-m-d H:i'), $t->movement, $t->quantity, $t->user->username]);
                }
            }
            fclose($out);
        }, 'inventory-report-'.now()->format('Y-m-d').'.csv');
    }
}