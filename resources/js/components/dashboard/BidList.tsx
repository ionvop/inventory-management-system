import { Gavel } from "lucide-react";
import { withUnit } from "@/lib/utils";
import type { Item } from "@/types";

interface BidListProps {
  items?: Item[];
}

export default function BidList({ items }: BidListProps) {
  if (!items || items.length === 0) {
    return (
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-6 text-center">
        <Gavel className="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
        <p className="text-sm text-gray-500 dark:text-gray-400">
          No active bids
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
          Active Bids
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
              <span className="text-sm font-semibold text-violet-600 dark:text-violet-400 tabular-nums">
                {withUnit(item.current_bid, item.unit)}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}