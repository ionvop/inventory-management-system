import { Badge } from "@/components/ui/badge";
import { withUnit } from "@/lib/utils";
import type { Report } from "@/types";

interface ReportPreviewProps {
  report: Report;
}

export default function ReportPreview({ report }: ReportPreviewProps) {
  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden print:border-none print:shadow-none">
      {/* Print Header */}
      <div className="hidden print:block mb-6">
        <h1 className="text-2xl font-bold text-gray-900">
          Inventory Report
        </h1>
        <p className="text-sm text-gray-500">
          Generated: {new Date(report.generated_time).toLocaleString()}
        </p>
      </div>

      {/* Screen Header */}
      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800 print:hidden">
        <div className="flex items-center justify-between">
          <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
            Report Preview
          </h3>
          <Badge variant="outline" className="text-xs">
            {report.format.toUpperCase()}
          </Badge>
        </div>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Generated: {new Date(report.generated_time).toLocaleString()}
        </p>
      </div>

      {/* Table */}
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Item
              </th>
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Unit
              </th>
              <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Stock
              </th>
              <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Min
              </th>
              <th className="text-center px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
            {report.items.map((row) => (
              <tr
                key={row.id}
                className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
              >
                <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                  {row.name}
                </td>
                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {row.unit}
                </td>
                <td className="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white tabular-nums">
                  {withUnit(row.current_stock, row.unit)}
                </td>
                <td className="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400 tabular-nums">
                  {withUnit(row.minimum_stock, row.unit)}
                </td>
                <td className="px-4 py-3 text-center">
                  <Badge
                    className={
                      row.is_low_stock
                        ? "bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 text-xs"
                        : "bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800 text-xs"
                    }
                  >
                    {row.is_low_stock ? "Low Stock" : "Normal"}
                  </Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {report.items.length === 0 && (
        <div className="p-8 text-center">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            No data available for the selected period
          </p>
        </div>
      )}
    </div>
  );
}