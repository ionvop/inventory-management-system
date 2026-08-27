import { ArrowDownToLine, ArrowUpFromLine } from "lucide-react";
import { format } from "date-fns";
import type { Transaction } from "@/types";

interface RecentTransactionsProps {
  transactions?: Transaction[];
}

function formatDate(timestamp: string | undefined): string {
  if (!timestamp) return "Unknown date";
  const date = new Date(timestamp);
  if (isNaN(date.getTime())) return "Unknown date";
  return format(date, "MMM d, h:mm a");
}

export default function RecentTransactions({
  transactions,
}: RecentTransactionsProps) {
  if (!transactions || transactions.length === 0) {
    return (
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-6 text-center">
        <ArrowDownToLine className="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
        <p className="text-sm text-gray-500 dark:text-gray-400">
          No recent transactions
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
          Recent Transactions
        </h3>
      </div>
      <div className="divide-y divide-gray-100 dark:divide-gray-800">
        {transactions.map((tx) => (
          <div key={tx.id} className="flex items-center gap-3 px-4 py-3">
            <div
              className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${
                tx.movement === "in"
                  ? "bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400"
                  : "bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400"
              }`}
            >
              {tx.movement === "in" ? (
                <ArrowDownToLine className="w-4 h-4" />
              ) : (
                <ArrowUpFromLine className="w-4 h-4" />
              )}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                {tx.item?.name ?? `Item #${tx.item_id}`}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {tx.user?.username ?? `User #${tx.user_id}`} ·{" "}
                {formatDate(tx.posted_at)}
              </p>
            </div>
            <div className="text-right flex-shrink-0">
              <span
                className={`text-sm font-semibold tabular-nums ${
                  tx.movement === "in"
                    ? "text-emerald-600 dark:text-emerald-400"
                    : "text-rose-600 dark:text-rose-400"
                }`}
              >
                {tx.movement === "in" ? "+" : "-"}
                {tx.quantity}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}