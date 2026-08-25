import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { AlertCircle } from "lucide-react";
import { toast } from "sonner";
import ReportGenerator from "@/components/reports/ReportGenerator";
import ReportPreview from "@/components/reports/ReportPreview";
import { TableSkeleton } from "@/components/shared/LoadingSkeleton";
import type { Report } from "@/types";

export default function Reports() {
  const [reportParams, setReportParams] = useState<{
    date_from?: string;
    date_to?: string;
    format: "json" | "csv" | "pdf";
  } | null>(null);

  const { data: report, isLoading, error } = useQuery<Report>({
    queryKey: ["report", reportParams],
    queryFn: () => api.getReport(reportParams!),
    enabled: !!reportParams,
  });

  const handleGenerate = (params: {
    date_from?: string;
    date_to?: string;
    format: "json" | "csv" | "pdf";
  }) => {
    if (params.format === "csv" || params.format === "pdf") {
      // For CSV/PDF, trigger download via direct URL
      const baseUrl = import.meta.env.VITE_API_BASE_URL || "";
      const userId = localStorage.getItem("inventory_current_user");
      let userIdHeader = "";
      if (userId) {
        try {
          const u = JSON.parse(userId);
          userIdHeader = String(u.id);
        } catch {
          // ignore
        }
      }

      const searchParams = new URLSearchParams();
      if (params.date_from) searchParams.set("date_from", params.date_from);
      if (params.date_to) searchParams.set("date_to", params.date_to);
      searchParams.set("format", params.format);

      const apiPrefix = import.meta.env.VITE_API_PREFIX || "/api";
      const url = `${baseUrl}${apiPrefix}/reports/inventory?${searchParams.toString()}`;

      // Use fetch to download with headers
      fetch(url, {
        headers: {
          Accept: "application/json",
          "X-User-Id": userIdHeader,
        },
      })
        .then((res) => {
          if (!res.ok) throw new Error("Download failed");
          return res.blob();
        })
        .then((blob) => {
          const ext = params.format === "csv" ? "csv" : "pdf";
          const mimeType =
            params.format === "csv" ? "text/csv" : "application/pdf";
          const blobUrl = URL.createObjectURL(
            new Blob([blob], { type: mimeType })
          );
          const a = document.createElement("a");
          a.href = blobUrl;
          a.download = `inventory-report.${ext}`;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(blobUrl);
          toast.success(`Report downloaded as ${params.format.toUpperCase()}`);
        })
        .catch((err) => {
          toast.error(err.message || "Failed to download report");
        });
    } else {
      // JSON preview
      setReportParams(params);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
          Reports
        </h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Generate and download inventory reports
        </p>
      </div>

      <ReportGenerator onGenerate={handleGenerate} isGenerating={false} />

      {/* Error */}
      {error && (
        <div className="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-600 dark:text-red-400">
            {error instanceof ApiClientError
              ? error.message
              : "Failed to generate report"}
          </p>
        </div>
      )}

      {/* Loading */}
      {isLoading && <TableSkeleton rows={5} cols={8} />}

      {/* Report Preview */}
      {report && !isLoading && <ReportPreview report={report} />}
    </div>
  );
}