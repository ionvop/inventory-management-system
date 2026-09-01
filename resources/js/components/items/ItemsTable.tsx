import { Pencil, Trash2, AlertTriangle, ArrowUpDown } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { withUnit } from "@/lib/utils";
import type { Item } from "@/types";

interface ItemsTableProps {
  items: Item[];
  onEdit: (item: Item) => void;
  onDelete: (item: Item) => void;
  sortBy?: string;
  sortOrder?: "asc" | "desc";
  onSort?: (column: string) => void;
}

export default function ItemsTable({
  items,
  onEdit,
  onDelete,
  sortBy: _sortBy,
  sortOrder: _sortOrder,
  onSort,
}: ItemsTableProps) {
  const SortHeader = ({
    column,
    label,
  }: {
    column: string;
    label: string;
  }) => (
    <button
      onClick={() => onSort?.(column)}
      className="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hover:text-gray-700 dark:hover:text-gray-200 transition-colors"
    >
      {label}
      <ArrowUpDown className="w-3 h-3" />
    </button>
  );

  return (
    <>
      {/* Desktop Table */}
      <div className="hidden md:block rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
              <th className="text-left px-4 py-3">
                <SortHeader column="name" label="Name" />
              </th>
              <th className="text-left px-4 py-3">
                <SortHeader column="unit" label="Unit" />
              </th>
              <th className="text-right px-4 py-3">
                <SortHeader column="current_stock" label="Stock" />
              </th>
              <th className="text-right px-4 py-3">
                <SortHeader column="minimum_stock" label="Min" />
              </th>
              <th className="text-center px-4 py-3">
                <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Status
                </span>
              </th>
              <th className="text-right px-4 py-3">
                <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Actions
                </span>
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
            {items.map((item) => {
              const isLowStock = item.is_low_stock;
              return (
                <tr
                  key={item.id}
                  className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                >
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-gray-900 dark:text-white text-sm">
                        {item.name}
                      </span>
                      {isLowStock && (
                        <AlertTriangle className="w-3.5 h-3.5 text-amber-500" />
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    {item.unit}
                  </td>
                  <td className="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white tabular-nums">
                    {withUnit(item.current_stock, item.unit)}
                  </td>
                  <td className="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400 tabular-nums">
                    {withUnit(item.minimum_stock, item.unit)}
                  </td>
                  <td className="px-4 py-3 text-center">
                    {isLowStock ? (
                      <Badge className="bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 text-xs">
                        Low Stock
                      </Badge>
                    ) : (
                      <Badge
                        variant="outline"
                        className="text-xs border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/30"
                      >
                        OK
                      </Badge>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => onEdit(item)}
                      >
                        <Pencil className="w-3.5 h-3.5" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8 text-red-500 hover:text-red-600"
                        onClick={() => onDelete(item)}
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </Button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Mobile Cards */}
      <div className="md:hidden space-y-3">
        {items.map((item) => {
          const isLowStock = item.is_low_stock;
          return (
            <div
              key={item.id}
              className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4"
            >
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-gray-900 dark:text-white truncate text-sm">
                      {item.name}
                    </h3>
                    {isLowStock && (
                      <AlertTriangle className="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                    )}
                  </div>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {item.unit}
                  </p>
                </div>
                <div className="flex items-center gap-1 flex-shrink-0">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    onClick={() => onEdit(item)}
                  >
                    <Pencil className="w-3.5 h-3.5" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-red-500 hover:text-red-600"
                    onClick={() => onDelete(item)}
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </Button>
                </div>
              </div>
              <div className="flex items-center gap-4 mt-3">
                <div>
                  <p className="text-lg font-bold text-gray-900 dark:text-white tabular-nums">
                    {withUnit(item.current_stock, item.unit)}
                  </p>
                  <p className="text-[10px] text-gray-500 dark:text-gray-400 uppercase">
                    Stock
                  </p>
                </div>
                <div className="w-px h-6 bg-gray-200 dark:bg-gray-700" />
                <div>
                  <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 tabular-nums">
                    {withUnit(item.minimum_stock, item.unit)}
                  </p>
                  <p className="text-[10px] text-gray-500 dark:text-gray-400 uppercase">
                    Min
                  </p>
                </div>
                {isLowStock && (
                  <Badge className="ml-auto bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 text-xs">
                    Low Stock
                  </Badge>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </>
  );
}