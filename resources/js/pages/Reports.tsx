import { router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface ColumnTotals {
    quantity: number;
    total_cost: number;
}

interface ReportRow {
    supplier_item_id: number;
    supplier_id: number | null;
    supplier_name: string | null;
    item_code: string | null;
    item_description: string | null;
    unit: string | null;
    batch_numbers: string | null;
    expiration_dates: string | null;
    contract_price: string;
    beginning: ColumnTotals;
    received: ColumnTotals;
    return_from_ward: ColumnTotals;
    return_to_supplier: ColumnTotals;
    transfer_to_pharmacy: ColumnTotals;
    consumption: ColumnTotals;
    write_off: ColumnTotals;
    ending: ColumnTotals;
}

interface ReportGroup {
    supplier_id: number | null;
    supplier_name: string | null;
    rows: ReportRow[];
    subtotal: Record<string, ColumnTotals>;
}

interface Report {
    period: { id: number; year: number; month: number; status: string };
    groups: ReportGroup[];
    grand_total: Record<string, ColumnTotals>;
}

interface PeriodOption {
    id: number;
    year: number;
    month: number;
    status: 'open' | 'closed';
}

interface ReportsProps {
    periods: PeriodOption[];
    selectedPeriodId: number | null;
    report: Report | null;
}

const monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

/** The movement columns, in report order (FR-7.1). */
const columns: { key: keyof ReportRow; label: string }[] = [
    { key: 'beginning', label: 'Beginning' },
    { key: 'received', label: 'Received' },
    { key: 'return_from_ward', label: 'Return from Ward' },
    { key: 'return_to_supplier', label: 'Return to Supplier' },
    { key: 'transfer_to_pharmacy', label: 'Transfer to Pharmacy' },
    { key: 'consumption', label: 'Consumption' },
    { key: 'write_off', label: 'Write-off' },
    { key: 'ending', label: 'Ending' },
];

function formatQuantity(value: number): string {
    return value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function formatCost(value: number): string {
    return value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function periodLabel(period: { year: number; month: number }): string {
    return `${monthNames[period.month - 1]} ${period.year}`;
}

function TotalsCell({ totals }: { totals: ColumnTotals }) {
    return (
        <td className="px-3 py-2 text-right align-top whitespace-nowrap">
            <div className="text-foreground">
                {formatQuantity(totals.quantity)}
            </div>
            <div className="text-xs text-muted-foreground">
                {formatCost(totals.total_cost)}
            </div>
        </td>
    );
}

export default function Reports({
    periods,
    selectedPeriodId,
    report,
}: ReportsProps) {
    const onSelectPeriod = (id: number) => {
        router.get('/reports', { period_id: id }, { preserveState: true });
    };

    return (
        <AppLayout title="Monthly report">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-xl font-bold text-foreground">
                        Monthly report
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Issuance of Drugs and Medicines and Supply Consignment,
                        grouped by supplier. Figures are derived from the
                        transaction log; a closed period reproduces its frozen
                        snapshot.
                    </p>
                </div>

                {periods.length > 0 && (
                    <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                        Period
                        <select
                            value={selectedPeriodId ?? ''}
                            onChange={(event) =>
                                onSelectPeriod(Number(event.target.value))
                            }
                            className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                        >
                            {periods.map((period) => (
                                <option key={period.id} value={period.id}>
                                    {periodLabel(period)}
                                    {period.status === 'closed'
                                        ? ' (closed)'
                                        : ''}
                                </option>
                            ))}
                        </select>
                    </label>
                )}
            </div>

            {!report && (
                <div className="rounded-lg border border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground">
                    No periods yet. A period is created automatically when the
                    first transaction for a month is recorded.
                </div>
            )}

            {report && (
                <>
                    <div className="mb-4 flex items-center gap-2">
                        <h2 className="text-sm font-semibold text-foreground">
                            {periodLabel(report.period)}
                        </h2>
                        <span
                            className={
                                report.period.status === 'closed'
                                    ? 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                                    : 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                            }
                        >
                            {report.period.status === 'closed'
                                ? 'Closed'
                                : 'Open'}
                        </span>
                    </div>

                    {report.groups.length === 0 ? (
                        <div className="rounded-lg border border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground">
                            No stock movements were recorded in this period.
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-lg border border-border bg-card">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-3 py-3 font-medium">
                                            Item
                                        </th>
                                        <th className="px-3 py-3 font-medium">
                                            Unit
                                        </th>
                                        <th className="px-3 py-3 font-medium">
                                            Batch no.
                                        </th>
                                        <th className="px-3 py-3 font-medium">
                                            Expiration
                                        </th>
                                        <th className="px-3 py-3 text-right font-medium">
                                            Contract price
                                        </th>
                                        {columns.map((column) => (
                                            <th
                                                key={column.key}
                                                className="px-3 py-3 text-right font-medium"
                                            >
                                                {column.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {report.groups.map((group) => (
                                        <GroupRows
                                            key={group.supplier_id ?? 'none'}
                                            group={group}
                                        />
                                    ))}
                                    <tr className="border-t-2 border-border bg-muted/50 font-semibold">
                                        <td
                                            className="px-3 py-3 text-foreground"
                                            colSpan={5}
                                        >
                                            Grand total
                                        </td>
                                        {columns.map((column) => (
                                            <TotalsCell
                                                key={column.key}
                                                totals={
                                                    report.grand_total[
                                                        column.key
                                                    ]
                                                }
                                            />
                                        ))}
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    )}
                </>
            )}
        </AppLayout>
    );
}

function GroupRows({ group }: { group: ReportGroup }) {
    return (
        <>
            <tr className="border-b border-border bg-muted/30">
                <td
                    className="px-3 py-2 text-xs font-semibold text-foreground uppercase"
                    colSpan={5}
                >
                    {group.supplier_name ?? 'Unassigned supplier'}
                </td>
                {columns.map((column) => (
                    <TotalsCell
                        key={column.key}
                        totals={group.subtotal[column.key]}
                    />
                ))}
            </tr>
            {group.rows.map((row) => (
                <tr
                    key={row.supplier_item_id}
                    className="border-b border-border last:border-0"
                >
                    <td className="px-3 py-2 align-top text-foreground">
                        {row.item_code ?? '—'}
                        {row.item_description ? (
                            <span className="text-muted-foreground">
                                {' '}
                                — {row.item_description}
                            </span>
                        ) : null}
                    </td>
                    <td className="px-3 py-2 align-top text-muted-foreground">
                        {row.unit ?? '—'}
                    </td>
                    <td className="px-3 py-2 align-top text-muted-foreground">
                        {row.batch_numbers ?? '—'}
                    </td>
                    <td className="px-3 py-2 align-top text-muted-foreground">
                        {row.expiration_dates ?? '—'}
                    </td>
                    <td className="px-3 py-2 text-right align-top whitespace-nowrap text-muted-foreground">
                        {formatCost(Number(row.contract_price))}
                    </td>
                    {columns.map((column) => (
                        <TotalsCell
                            key={column.key}
                            totals={row[column.key] as ColumnTotals}
                        />
                    ))}
                </tr>
            ))}
        </>
    );
}
