import { AlertTriangle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { withUnit } from "@/lib/utils";
import type { Item } from "@/types";

interface LowStockListProps {
  items?: Item[];
}

export default function LowStockList({ items }: LowStockListProps) {
  if (!items || items.length === 0) {
    return (
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-6 text-center">
        <AlertTriangle className="w-8 h-8 text-emerald-400 mx-auto mb-2" />
        <p className="text-sm text-gray-500 dark:text-gray-400">
          All items are well stocked!
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
          Low Stock Items
        </h3>
      </div>
      <div className="divide-y divide-gray-100 dark:divide-gray-800">
        {items.map((item) => (
          <div
            key={item.id}
            className="flex items-center justify-between px-4 py-3"
          >
            <div className="min-w-0 flex-1">
              <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                {item.name}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {item.unit}
              </p>
            </div>
            <div className="flex items-center gap-3 flex-shrink-0">
              <span className="text-sm font-semibold text-amber-600 dark:text-amber-400 tabular-nums">
                {withUnit(item.current_stock, item.unit)}
              </span>
              <Badge
                variant="outline"
                className="text-xs border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30"
              >
                Min: {withUnit(item.minimum_stock, item.unit)}
              </Badge>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}