<?php
// app/Http/Controllers/Api/ReportController.php
namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Collection;

class ReportController extends Controller
{
    public function inventory(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $format = $request->query('format', 'excel');
        $timezone = Setting::get('timezone', config('app.timezone'));

        $items = Item::withStock()
            ->with(['transactions' => function ($q) use ($dateFrom, $dateTo, $timezone) {
                $q->when($dateFrom, fn ($q, $v) => $q->where('posted_at', '>=', Carbon::parse($v, $timezone)->startOfDay()->utc()))
                  ->when($dateTo, fn ($q, $v) => $q->where('posted_at', '<=', Carbon::parse($v, $timezone)->endOfDay()->utc()))
                  ->with('user:id,username')
                  ->orderBy('posted_at');
            }])
            ->get();

        return match ($format) {
            'csv' => $this->csv($items, $timezone),
            'excel' => $this->excel($items, $timezone),
            'pdf' => Pdf::loadView('reports.inventory', [
                'items' => $items,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'timezone' => $timezone,
            ])->download('inventory-report-'.now()->timezone($timezone)->format('Y-m-d').'.pdf'),
        };
    }

    private function csv(Collection $items, string $timezone): StreamedResponse
    {
        return response()->streamDownload(function () use ($items, $timezone) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Unit', 'Current Stock', 'Minimum Stock', 'Transaction Date', 'Movement', 'Quantity', 'User']);
            foreach ($items as $item) {
                if ($item->transactions->isEmpty()) {
                    fputcsv($out, [$item->name, $item->unit, $item->current_stock, $item->minimum_stock, '', '', '', '']);
                }
                foreach ($item->transactions as $t) {
                    fputcsv($out, [$item->name, $item->unit, $item->current_stock, $item->minimum_stock, $t->posted_at->timezone($timezone)->format('Y-m-d H:i'), $t->movement, $t->quantity, $t->user->username]);
                }
            }
            fclose($out);
        }, 'inventory-report-'.now()->timezone($timezone)->format('Y-m-d').'.csv');
    }

    private function excel(Collection $items, string $timezone): StreamedResponse
    {
        return response()->streamDownload(function () use ($items, $timezone) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['Item', 'Unit', 'Current Stock', 'Minimum Stock', 'Transaction Date', 'Movement', 'Quantity', 'User'];
            $sheet->fromArray($headers, null, 'A1');

            $row = 2;
            foreach ($items as $item) {
                if ($item->transactions->isEmpty()) {
                    $sheet->fromArray([
                        $item->name, $item->unit, $item->current_stock, $item->minimum_stock, '', '', '', '',
                    ], null, 'A'.$row);
                    $row++;
                }
                foreach ($item->transactions as $t) {
                    $sheet->fromArray([
                        $item->name, $item->unit, $item->current_stock, $item->minimum_stock,
                        $t->posted_at->timezone($timezone)->format('Y-m-d H:i'), $t->movement, $t->quantity, $t->user->username,
                    ], null, 'A'.$row);
                    $row++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'inventory-report-'.now()->timezone($timezone)->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}