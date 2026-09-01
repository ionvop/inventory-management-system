<?php
// app/Http/Controllers/Api/ReportController.php
namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\Setting;
use App\Models\Transaction;
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
            'excel' => $this->excel($items, $timezone, $dateFrom),
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

    private function excel(Collection $items, string $timezone, ?string $dateFrom): StreamedResponse
    {
        return response()->streamDownload(function () use ($items, $timezone, $dateFrom) {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            $usedTitles = [];
            foreach ($items as $item) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($this->uniqueSheetTitle($item->name, $usedTitles));

                $this->writeItemSheet($sheet, $item, $timezone, $dateFrom);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'inventory-report-'.now()->timezone($timezone)->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Write a single item's daily transaction summary onto a worksheet.
     */
    private function writeItemSheet($sheet, Item $item, string $timezone, ?string $dateFrom): void
    {
        $headers = ['DATE', 'IN', 'OUT', 'BALANCE', 'DIFF', 'USER'];
        $sheet->fromArray($headers, null, 'A1');

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $openingBalance = $this->openingBalance($item, $timezone, $dateFrom);

        $days = [];
        foreach ($item->transactions as $t) {
            $day = $t->posted_at->timezone($timezone)->format('Y-m-d');
            $days[$day][] = $t;
        }
        ksort($days);

        $row = 2;
        $previousBalance = $openingBalance;
        foreach ($days as $day => $transactions) {
            $in = 0;
            $out = 0;
            $userCounts = [];
            $userLatest = [];

            foreach ($transactions as $t) {
                if ($t->movement === 'in') {
                    $in += $t->quantity;
                } else {
                    $out += $t->quantity;
                }

                $userCounts[$t->user_id] = ($userCounts[$t->user_id] ?? 0) + 1;
                if (!isset($userLatest[$t->user_id]) || $t->posted_at->greaterThan($userLatest[$t->user_id])) {
                    $userLatest[$t->user_id] = $t->posted_at;
                }
            }

            $balance = $previousBalance + $in - $out;
            $diff = $balance - $previousBalance;

            // User with the most transactions; tie-break by the latest transaction.

            $topUserId = null;
            $topCount = -1;
            $topLatest = null;
            foreach ($userCounts as $userId => $count) {
                $latest = $userLatest[$userId];
                if ($count > $topCount || ($count === $topCount && $latest->greaterThan($topLatest))) {
                    $topCount = $count;
                    $topUserId = $userId;
                    $topLatest = $latest;
                }
            }

            $username = '';
            if ($topUserId !== null) {
                foreach ($transactions as $t) {
                    if ($t->user_id === $topUserId) {
                        $username = $t->user->username;
                        break;
                    }
                }
            }

            $sheet->fromArray([
                $day, $in, $out, $balance, $diff, $username,
            ], null, 'A'.$row);

            $previousBalance = $balance;
            $row++;
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->calculateColumnWidths();
    }

    /**
     * Stock count for the item at the start of the report period.
     */
    private function openingBalance(Item $item, string $timezone, ?string $dateFrom): int
    {
        $query = Transaction::where('item_id', $item->id);

        if ($dateFrom) {
            $query->where('posted_at', '<', Carbon::parse($dateFrom, $timezone)->startOfDay()->utc());
        }

        $in = (int) (clone $query)->where('movement', 'in')->sum('quantity');
        $out = (int) (clone $query)->where('movement', 'out')->sum('quantity');

        return $in - $out;
    }

    /**
     * Build a unique, valid worksheet title from an item name.
     */
    private function uniqueSheetTitle(string $name, array &$usedTitles): string
    {
        $base = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $name);
        $base = trim($base);
        $base = mb_substr($base, 0, 31);

        if ($base === '') {
            $base = 'Item';
        }

        $title = $base;
        $i = 2;
        while (in_array($title, $usedTitles, true)) {
            $suffix = '-'.$i;
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $i++;
        }

        $usedTitles[] = $title;

        return $title;
    }
}