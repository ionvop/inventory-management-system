import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import SummaryCards from "@/components/dashboard/SummaryCards";
import LowStockList from "@/components/dashboard/LowStockList";
import RecentTransactions from "@/components/dashboard/RecentTransactions";
import { SummaryCardSkeleton } from "@/components/shared/LoadingSkeleton";
import { AlertCircle } from "lucide-react";

export default function Dashboard() {
  const { data, isLoading, error } = useQuery({
    queryKey: ["dashboard-summary"],
    queryFn: api.getDashboardSummary,
    refetchInterval: 30_000,
  });

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
          Dashboard
        </h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Overview of your inventory
        </p>
      </div>

      {isLoading && <SummaryCardSkeleton />}

      {error && (
        <div className="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-600 dark:text-red-400">
            {(error as Error).message || "Failed to load dashboard data"}
          </p>
        </div>
      )}

      {data && (
        <>
          <SummaryCards summary={data} />

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <LowStockList items={data.low_stock_items} />
            <RecentTransactions transactions={data.recent_transactions} />
          </div>
        </>
      )}
    </div>
  );
}