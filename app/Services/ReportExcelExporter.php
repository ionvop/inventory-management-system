<?php

namespace App\Services;

use App\Models\Period;
use App\Models\Profile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Renders the monthly report as an Excel workbook (FR-7.3).
 *
 * The layout reproduces the department's July sheet as specified in
 * docs/report-spec.md: a title band, a two-row header, supplier bands, one row
 * per (supplier item, batch), subtotals, and a remarks block. Derived columns
 * are written as live formulas so the workbook recalculates, and the spec's
 * known formula errors are corrected (transfer cost uses price x qty, the
 * ending balance subtracts transfers and adds ward returns).
 *
 * Deliberate deviations from the July sheet, all documented in the spec's
 * section 9 or agreed during design:
 * - A separate Write-off column is kept (the app has six movement types; the
 *   sheet folded write-offs into Consumption).
 * - Per-supplier subtotal rows are added (proposal FR-7.1), which the July
 *   sheet lacks; quantity columns are subtotalled too.
 * - The ending balance is item-level, so it is shown on the first batch row of
 *   each supplier item and zero on the rest.
 * - The generation date is written as a static value, not =TODAY() (spec fix 10).
 */
class ReportExcelExporter
{
    /** The last column of the sheet (A..V). */
    protected const string LAST_COLUMN = 'V';

    /** Column widths, keyed by column letter (report-spec section 2). */
    protected const array COLUMN_WIDTHS = [
        'A' => 8.14, 'B' => 27.14, 'C' => 3.29, 'D' => 8.00, 'E' => 9.43, 'F' => 8.71,
        'G' => 2.86, 'H' => 10.86, 'I' => 3.43, 'J' => 11.00, 'K' => 4.00, 'L' => 8.43,
        'M' => 3.00, 'N' => 7.57, 'O' => 3.57, 'P' => 12.14, 'Q' => 3.00, 'R' => 10.29,
        'S' => 3.00, 'T' => 10.29, 'U' => 3.29, 'V' => 11.00,
    ];

    /** The quantity columns, in sheet order. */
    protected const array QUANTITY_COLUMNS = ['G', 'I', 'K', 'M', 'O', 'Q', 'S', 'U'];

    /** The total-cost columns, in sheet order. */
    protected const array COST_COLUMNS = ['H', 'J', 'L', 'N', 'P', 'R', 'T', 'V'];

    /** The report column key to its quantity column letter. */
    protected const array MOVEMENT_COLUMNS = [
        'beginning' => 'G',
        'received' => 'I',
        'return_from_ward' => 'K',
        'return_to_supplier' => 'M',
        'transfer_to_pharmacy' => 'O',
        'consumption' => 'Q',
        'write_off' => 'S',
        'ending' => 'U',
    ];

    protected const string TITLE_FILL = 'CCC0DA';

    protected const string BAND_FILL_PRIMARY = 'B7DEE8';

    protected const string BAND_FILL_SECONDARY = '92CDDC';

    protected const string SUBTOTAL_FILL = 'FFFFCC';

    protected const string REMARK_COLOR = 'FF0000';

    public function __construct(protected ReportService $reports) {}

    /**
     * Build the workbook and return it as a downloadable response.
     */
    public function download(Period $period): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'inventory-report');

        if ($path === false) {
            throw new \RuntimeException('Unable to create a temporary file for the report.');
        }

        $path .= '.xlsx';

        (new Xlsx($this->build($period)))->save($path);

        return response()
            ->download($path, $this->filename($period))
            ->deleteFileAfterSend(true);
    }

    /**
     * The download filename for a period.
     */
    public function filename(Period $period): string
    {
        return sprintf('inventory-report-%04d-%02d.xlsx', $period->year, $period->month);
    }

    /**
     * Build the report workbook for a period.
     */
    public function build(Period $period): Spreadsheet
    {
        $report = $this->reports->buildDetailed($period);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sheetName($period));

        $this->configurePage($sheet);
        $this->configureColumns($sheet);
        $this->writeTitles($sheet, $period);
        $this->writeHeader($sheet, $period);

        $row = 6;
        $firstGroup = true;
        $subtotalRows = [];

        foreach ($report['groups'] as $group) {
            if ($firstGroup) {
                // The first supplier band sits inside the header row (spec 3.3).
                $this->writeBand($sheet, 5, $group['supplier_name'], true);
                $firstGroup = false;
            } else {
                $this->writeBand($sheet, $row, $group['supplier_name'], false);
                $row++;
            }

            $bodyStart = $row;

            foreach ($group['rows'] as $itemRow) {
                $this->writeItemRow($sheet, $row, $itemRow, $bodyStart);
                $row++;
            }

            $this->writeSubtotal($sheet, $row, $bodyStart, $row - 1);
            $subtotalRows[] = $row;
            $row++;
        }

        $this->writeGrandTotal($sheet, $row, $subtotalRows);
        $row += 2; // Skip one blank row before the remarks block.

        $this->writeRemarks($sheet, $row, $report['remarks'], $period);

        return $spreadsheet;
    }

    /**
     * The sheet tab name: the three-letter uppercase month (spec section 1).
     */
    protected function sheetName(Period $period): string
    {
        return strtoupper(substr($this->monthName($period), 0, 3));
    }

    /**
     * The full month name for a period.
     */
    protected function monthName(Period $period): string
    {
        return Carbon::create($period->year, $period->month, 1)->format('F');
    }

    /**
     * Apply the landscape Folio page setup (spec section 1).
     */
    protected function configurePage(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(14);

        $sheet->getPageMargins()
            ->setLeft(0.25)
            ->setRight(0.0)
            ->setTop(0.25)
            ->setBottom(0.25);
    }

    /**
     * Apply the column widths (spec section 2).
     */
    protected function configureColumns(Worksheet $sheet): void
    {
        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * Write the merged title band in rows 1-3 (spec 3.1).
     */
    protected function writeTitles(Worksheet $sheet, Period $period): void
    {
        $last = self::LAST_COLUMN;

        $titles = [
            1 => 'Davao Regional Medical center',
            2 => 'Nutrition and Dietetics Department',
            3 => ' ISSUANCE OF DRUGS AND MEDICINES AND SUPPLY CONSIGNMENT FOR THE MONTH OF : '
                .strtoupper($this->monthName($period)).' '.$period->year,
        ];

        foreach ($titles as $row => $text) {
            $sheet->mergeCells("A{$row}:{$last}{$row}");
            $sheet->setCellValue("A{$row}", $text);
            $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 10, 'bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF'.self::TITLE_FILL],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        $sheet->getRowDimension(1)->setRowHeight(12.75);
    }

    /**
     * Write the two-row column header in rows 4-5 (spec 3.2).
     */
    protected function writeHeader(Worksheet $sheet, Period $period): void
    {
        $last = self::LAST_COLUMN;
        $month = strtoupper($this->monthName($period));

        foreach (['C', 'D', 'E', 'F'] as $column) {
            $sheet->mergeCells("{$column}4:{$column}5");
        }

        foreach (self::QUANTITY_COLUMNS as $quantityColumn) {
            $costColumn = $this->costColumn($quantityColumn);
            $sheet->mergeCells("{$quantityColumn}4:{$costColumn}4");
        }

        $sheet->setCellValue('A4', 'ITEM');
        $sheet->setCellValue('A5', 'CODE');
        $sheet->setCellValue('B4', 'ITEM DESCRIPTION');
        $sheet->setCellValue('C4', 'UNIT');
        $sheet->setCellValue('D4', 'BATCH NO.');
        $sheet->setCellValue('E4', 'EXPIRATION DATE');
        $sheet->setCellValue('F4', 'CONTRACT PRICE');

        $labels = [
            'G' => "BEGINNING BALANCE {$month} {$period->year}",
            'I' => "RECEIVED {$month} {$period->year}",
            'K' => 'RETURN FROM DIFFERENT WARDS',
            'M' => 'RETURN TO SUPPLIER',
            'O' => 'TRANSFER STOCK TO PHARMACY',
            'Q' => 'CONSUMPTION',
            'S' => 'WRITE-OFF',
            'U' => "BAL. {$month} {$period->year}",
        ];

        foreach ($labels as $column => $label) {
            $sheet->setCellValue("{$column}4", $label);
            $sheet->setCellValue("{$column}5", 'QTY.');
            $sheet->setCellValue($this->costColumn($column).'5', 'TOTAL COST');
        }

        $sheet->getStyle("A4:{$last}5")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 6, 'bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Row 5's QTY./TOTAL COST labels are regular weight (spec 3.2).
        $sheet->getStyle("G5:{$last}5")->getFont()->setBold(false);

        $sheet->getStyle('B4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF'.self::TITLE_FILL);

        $sheet->getStyle('A4')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('A5')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getRowDimension(4)->setRowHeight(24);
        $sheet->getRowDimension(5)->setRowHeight(18);
    }

    /**
     * Write a supplier band row (spec 3.3).
     */
    protected function writeBand(Worksheet $sheet, int $row, ?string $name, bool $primary): void
    {
        $this->styleBodyRow($sheet, $row);

        $sheet->setCellValue("B{$row}", $name ?? 'Unassigned supplier');
        $sheet->getStyle("B{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 6, 'bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FF'.($primary ? self::BAND_FILL_PRIMARY : self::BAND_FILL_SECONDARY),
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    /**
     * Write one item row, with live formulas for the derived columns.
     *
     * @param  array<string, mixed>  $item
     */
    protected function writeItemRow(Worksheet $sheet, int $row, array $item, int $bodyStart): void
    {
        $this->styleBodyRow($sheet, $row);

        $sheet->setCellValue("A{$row}", $item['item_code']);
        $sheet->setCellValue("B{$row}", $item['item_description']);
        $sheet->setCellValue("C{$row}", $item['unit']);
        $sheet->setCellValueExplicit(
            "D{$row}",
            (string) ($item['batch_number'] ?? ''),
            DataType::TYPE_STRING,
        );

        if ($item['expiration_date'] !== null) {
            $sheet->setCellValue("E{$row}", $item['expiration_date']);
        }

        $sheet->setCellValue("F{$row}", (float) $item['contract_price']);

        foreach (self::MOVEMENT_COLUMNS as $key => $quantityColumn) {
            /** @var array{quantity: float, total_cost: float} $totals */
            $totals = $item[$key];

            $sheet->setCellValue("{$quantityColumn}{$row}", $totals['quantity']);
        }

        // Derived columns are live formulas (spec section 4), with the spec's
        // known errors corrected: transfer cost is price x qty, and the ending
        // balance subtracts transfers and write-offs and adds ward returns.
        $sheet->setCellValue("H{$row}", "=F{$row}*G{$row}");
        $sheet->setCellValue("J{$row}", "=F{$row}*I{$row}");
        $sheet->setCellValue("L{$row}", "=F{$row}*K{$row}");
        $sheet->setCellValue("N{$row}", "=F{$row}*M{$row}");
        $sheet->setCellValue("P{$row}", "=F{$row}*O{$row}");
        $sheet->setCellValue("R{$row}", "=F{$row}*Q{$row}");
        $sheet->setCellValue("T{$row}", "=F{$row}*S{$row}");

        // The ending balance is item-level, so the first batch row of a
        // supplier item sums every batch row's movements; later rows show zero.
        if ($row === $bodyStart) {
            $sheet->setCellValue(
                "U{$row}",
                "=G{$row}+SUM(I{$bodyStart}:I{$row})+SUM(K{$bodyStart}:K{$row})"
                ."-SUM(M{$bodyStart}:M{$row})-SUM(O{$bodyStart}:O{$row})"
                ."-SUM(Q{$bodyStart}:Q{$row})-SUM(S{$bodyStart}:S{$row})",
            );
        } else {
            $sheet->setCellValue("U{$row}", 0);
        }

        $sheet->setCellValue("V{$row}", "=F{$row}*U{$row}");
    }

    /**
     * Write a per-supplier subtotal row (proposal FR-7.1).
     */
    protected function writeSubtotal(Worksheet $sheet, int $row, int $bodyStart, int $bodyEnd): void
    {
        $this->styleSubtotalRow($sheet, $row);

        $sheet->setCellValue("B{$row}", 'SUB TOTAL');

        foreach (array_merge(self::QUANTITY_COLUMNS, self::COST_COLUMNS) as $column) {
            $sheet->setCellValue("{$column}{$row}", "=SUM({$column}{$bodyStart}:{$column}{$bodyEnd})");
        }
    }

    /**
     * Write the grand total row, summing the per-supplier subtotals.
     *
     * @param  array<int, int>  $subtotalRows
     */
    protected function writeGrandTotal(Worksheet $sheet, int $row, array $subtotalRows): void
    {
        $this->styleSubtotalRow($sheet, $row);

        $sheet->setCellValue("B{$row}", 'GRAND TOTAL');

        foreach (array_merge(self::QUANTITY_COLUMNS, self::COST_COLUMNS) as $column) {
            if ($subtotalRows === []) {
                $sheet->setCellValue("{$column}{$row}", 0);

                continue;
            }

            $references = implode(',', array_map(
                fn (int $subtotalRow): string => "{$column}{$subtotalRow}",
                $subtotalRows,
            ));

            $sheet->setCellValue("{$column}{$row}", "=SUM({$references})");
        }
    }

    /**
     * Write the remarks block and signature labels (spec 3.6, FR-7.2/7.3).
     *
     * @param  array<int, string>  $remarks
     */
    protected function writeRemarks(Worksheet $sheet, int $row, array $remarks, Period $period): void
    {
        $sheet->setCellValue("B{$row}", 'Inventory Remarks:');
        $sheet->getStyle("B{$row}")->applyFromArray([
            'font' => [
                'name' => 'Calibri',
                'size' => 8,
                'bold' => true,
                'color' => ['argb' => 'FF'.self::REMARK_COLOR],
            ],
        ]);

        // The generation date is a static value, not =TODAY() (spec fix 10).
        $sheet->setCellValue("N{$row}", now()->toDateString());
        $sheet->getStyle("N{$row}")->getNumberFormat()->setFormatCode('m/d/yyyy;@');
        $sheet->getStyle("N{$row}")->getFont()->setName('Calibri')->setSize(10);

        $row++;

        foreach ($remarks as $remark) {
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue("B{$row}", $remark);
            $sheet->getStyle("B{$row}:F{$row}")->applyFromArray([
                'font' => [
                    'name' => 'Calibri',
                    'size' => 8,
                    'color' => ['argb' => 'FF'.self::REMARK_COLOR],
                ],
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => Alignment::VERTICAL_TOP,
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(24);
            $row++;
        }

        $preparedBy = $this->preparedBy($period);

        $sheet->setCellValue("L{$row}", 'Prepared by:'.($preparedBy !== null ? ' '.$preparedBy : ''));
        $sheet->setCellValue("Q{$row}", 'Received by:');
        $sheet->getStyle("L{$row}")->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle("Q{$row}")->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle("Q{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * The name of the profile that closed the period, when it is closed.
     */
    protected function preparedBy(Period $period): ?string
    {
        if ($period->closed_by === null) {
            return null;
        }

        return Profile::query()->find($period->closed_by)?->name;
    }

    /**
     * Apply the shared body styling to a row (spec 3.4).
     */
    protected function styleBodyRow(Worksheet $sheet, int $row): void
    {
        $last = self::LAST_COLUMN;

        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 6],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFFFF'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Columns B and F carry no fill in the original item rows (spec 3.4).
        $sheet->getStyle("B{$row}")->getFill()->setFillType(Fill::FILL_NONE);
        $sheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_NONE);

        foreach (self::QUANTITY_COLUMNS as $column) {
            $sheet->getStyle("{$column}{$row}")->getNumberFormat()->setFormatCode('0');
            $sheet->getStyle("{$column}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (self::COST_COLUMNS as $column) {
            $sheet->getStyle("{$column}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$column}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('mm-dd-yy');
        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Apply the subtotal styling to a row (spec 3.5).
     */
    protected function styleSubtotalRow(Worksheet $sheet, int $row): void
    {
        $last = self::LAST_COLUMN;

        $sheet->getStyle("A{$row}:{$last}{$row}")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 6, 'bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF'.self::SUBTOTAL_FILL],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        foreach (array_merge(self::QUANTITY_COLUMNS, self::COST_COLUMNS) as $column) {
            $sheet->getStyle("{$column}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("{$column}{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("A{$row}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("A{$row}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("A{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
    }

    /**
     * The total-cost column paired with a quantity column.
     */
    protected function costColumn(string $quantityColumn): string
    {
        return chr(ord($quantityColumn) + 1);
    }
}
