import { Pencil, Trash2, ArrowDownToLine, ArrowUpFromLine } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { format } from "date-fns";
import type { Transaction } from "@/types";

function formatDateTime(timestamp: string | undefined): string {
  if (!timestamp) return "Unknown date";
  const date = new Date(timestamp);
  if (isNaN(date.getTime())) return "Unknown date";
  return format(date, "MMM d, h:mm a");
}

interface TransactionsTableProps {
  transactions: Transaction[];
  onEdit: (tx: Transaction) => void;
  onDelete: (tx: Transaction) => void;
}

export default function TransactionsTable({
  transactions,
  onEdit,
  onDelete,
}: TransactionsTableProps) {
  return (
    <>
      {/* Desktop Table */}
      <div className="hidden md:block rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Item
              </th>
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Type
              </th>
              <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Qty
              </th>
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                User
              </th>
              <th className="text-left px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Date
              </th>
              <th className="text-right px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
            {transactions.map((tx) => (
              <tr
                key={tx.id}
                className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
              >
                <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                  {tx.item?.name ?? `Item #${tx.item_id}`}
                </td>
                <td className="px-4 py-3">
                  <Badge
                    className={
                      tx.movement === "in"
                        ? "bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800"
                        : "bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800"
                    }
                  >
                    <span className="flex items-center gap-1">
                      {tx.movement === "in" ? (
                        <ArrowDownToLine className="w-3 h-3" />
                      ) : (
                        <ArrowUpFromLine className="w-3 h-3" />
                      )}
                      {tx.movement === "in" ? "In" : "Out"}
                    </span>
                  </Badge>
                </td>
                <td className="px-4 py-3 text-sm text-right font-semibold tabular-nums">
                  <span
                    className={
                      tx.movement === "in"
                        ? "text-emerald-600 dark:text-emerald-400"
                        : "text-rose-600 dark:text-rose-400"
                    }
                  >
                    {tx.movement === "in" ? "+" : "−"}
                    {tx.quantity}
                  </span>
                </td>
                <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {tx.user?.username ?? `User #${tx.user_id}`}
                </td>
                <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                  {formatDateTime(tx.posted_at)}
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8"
                      onClick={() => onEdit(tx)}
                    >
                      <Pencil className="w-3.5 h-3.5" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 text-red-500 hover:text-red-600"
                      onClick={() => onDelete(tx)}
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Mobile Cards */}
      <div className="md:hidden space-y-3">
        {transactions.map((tx) => (
          <div
            key={tx.id}
            className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4"
          >
            <div className="flex items-start justify-between gap-2">
              <div className="min-w-0 flex-1">
                <p className="font-semibold text-gray-900 dark:text-white text-sm truncate">
                  {tx.item?.name ?? `Item #${tx.item_id}`}
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  {tx.user?.username ?? `User #${tx.user_id}`} ·{" "}
                  {formatDateTime(tx.posted_at)}
                </p>
              </div>
              <div className="flex items-center gap-1 flex-shrink-0">
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8"
                  onClick={() => onEdit(tx)}
                >
                  <Pencil className="w-3.5 h-3.5" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 text-red-500 hover:text-red-600"
                  onClick={() => onDelete(tx)}
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </Button>
              </div>
            </div>
            <div className="flex items-center gap-3 mt-3">
              <Badge
                className={
                  tx.movement === "in"
                    ? "bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800"
                    : "bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800"
                }
              >
                <span className="flex items-center gap-1">
                  {tx.movement === "in" ? (
                    <ArrowDownToLine className="w-3 h-3" />
                  ) : (
                    <ArrowUpFromLine className="w-3 h-3" />
                  )}
                  {tx.movement === "in" ? "In" : "Out"}
                </span>
              </Badge>
              <span
                className={`text-lg font-bold tabular-nums ${
                  tx.movement === "in"
                    ? "text-emerald-600 dark:text-emerald-400"
                    : "text-rose-600 dark:text-rose-400"
                }`}
              >
                {tx.movement === "in" ? "+" : "−"}
                {tx.quantity}
              </span>
            </div>
          </div>
        ))}
      </div>
    </>
  );
}