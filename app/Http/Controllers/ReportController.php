<?php

namespace App\Http\Controllers;

use App\Models\Period;
use App\Services\ReportExcelExporter;
use App\Services\ReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Displays the department's monthly stock report (FR-7.1).
 *
 * The report is grouped by supplier and shows each supplier item's beginning
 * balance, a column per movement type, and the ending balance, with per-supplier
 * subtotals and a grand subtotal. It is read-only: every figure is derived from
 * the transaction log, and a closed period reproduces its frozen snapshot
 * (FR-7.4).
 *
 * Reporting is a supervisor/administrator activity, matching period closing,
 * since the report carries the department's sign-off (FR-7.3).
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reports,
        protected ReportExcelExporter $exporter,
    ) {}

    /**
     * Display the monthly report for a selected period.
     */
    public function index(): Response
    {
        $periods = Period::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $period = $this->selectedPeriod($periods);

        return Inertia::render('Reports', [
            'periods' => $periods->map(fn (Period $period) => [
                'id' => $period->id,
                'year' => $period->year,
                'month' => $period->month,
                'status' => $period->status,
            ]),
            'selectedPeriodId' => $period?->id,
            'report' => $period ? $this->reports->build($period) : null,
        ]);
    }

    /**
     * Export the monthly report as an Excel workbook (FR-7.3).
     */
    public function export(): BinaryFileResponse
    {
        $periods = Period::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $period = $this->selectedPeriod($periods);

        abort_if($period === null, 404, 'No period to export.');

        return $this->exporter->download($period);
    }

    /**
     * Resolve the period to report on.
     *
     * The requested period is used when it exists; otherwise the most recent
     * period is shown. Returns null when no periods have been recorded yet.
     *
     * @param  Collection<int, Period>  $periods
     */
    protected function selectedPeriod($periods): ?Period
    {
        $requestedId = Request::query('period_id');

        if ($requestedId !== null) {
            $requested = $periods->firstWhere('id', (int) $requestedId);

            if ($requested instanceof Period) {
                return $requested;
            }
        }

        $first = $periods->first();

        return $first instanceof Period ? $first : null;
    }
}
