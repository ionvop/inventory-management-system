<?php

namespace App\Services;

use App\Models\Period;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Renders the monthly report as a PDF (FR-7.3).
 *
 * The PDF is produced from the exact workbook the Excel export builds
 * (ReportExcelExporter::build), rendered through PhpSpreadsheet's dompdf
 * writer. Keeping a single source of truth for the layout means the PDF and the
 * .xlsx can never drift: the title band, header, supplier bands, subtotals,
 * remarks block and signature labels are identical in both outputs. The
 * workbook's own page setup (landscape Folio, the department's margins) is
 * carried into the PDF by the writer.
 */
class ReportPdfExporter
{
    public function __construct(protected ReportExcelExporter $excel) {}

    /**
     * Build the report workbook and return it as a downloadable PDF.
     */
    public function download(Period $period): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'inventory-report');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary file for the report.');
        }

        $path .= '.pdf';

        (new Dompdf($this->build($period)))->save($path);

        return response()
            ->download($path, $this->filename($period))
            ->deleteFileAfterSend(true);
    }

    /**
     * The download filename for a period.
     */
    public function filename(Period $period): string
    {
        return sprintf('inventory-report-%04d-%02d.pdf', $period->year, $period->month);
    }

    /**
     * Build the report workbook that the PDF is rendered from.
     *
     * The layout lives entirely in the Excel exporter; the PDF reuses it so the
     * two exports stay identical. Kept as a method so the rendering can be
     * tested without writing a file.
     */
    public function build(Period $period): Spreadsheet
    {
        return $this->excel->build($period);
    }
}
