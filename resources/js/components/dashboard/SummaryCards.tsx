import { Package, AlertTriangle, ArrowDownToLine, ArrowUpFromLine } from "lucide-react";
import type { DashboardSummary } from "@/types";

interface SummaryCardsProps {
  summary: DashboardSummary;
}

const cards = [
  {
    key: "total_items" as const,
    label: "Total Items",
    icon: Package,
    color: "text-indigo-600 dark:text-indigo-400",
    bg: "bg-indigo-50 dark:bg-indigo-950/30",
  },
  {
    key: "low_stock_count" as const,
    label: "Low Stock",
    icon: AlertTriangle,
    color: "text-amber-600 dark:text-amber-400",
    bg: "bg-amber-50 dark:bg-amber-950/30",
  },
  {
    key: "today_in" as const,
    label: "Today In",
    icon: ArrowDownToLine,
    color: "text-emerald-600 dark:text-emerald-400",
    bg: "bg-emerald-50 dark:bg-emerald-950/30",
  },
  {
    key: "today_out" as const,
    label: "Today Out",
    icon: ArrowUpFromLine,
    color: "text-rose-600 dark:text-rose-400",
    bg: "bg-rose-50 dark:bg-rose-950/30",
  },
];

export default function SummaryCards({ summary }: SummaryCardsProps) {
  const values: Record<string, number> = {
    total_items: summary.total_items,
    low_stock_count: summary.low_stock_count,
    today_in: summary.today_transactions.in_count,
    today_out: summary.today_transactions.out_count,
  };

  return (
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
      {cards.map(({ key, label, icon: Icon, color, bg }) => (
        <div
          key={key}
          className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4 transition-shadow hover:shadow-md"
        >
          <div className="flex items-center gap-3">
            <div className={`w-10 h-10 rounded-xl ${bg} flex items-center justify-center flex-shrink-0`}>
              <Icon className={`w-5 h-5 ${color}`} />
            </div>
            <div className="min-w-0">
              <p className="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
                {values[key]}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                {label}
              </p>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}