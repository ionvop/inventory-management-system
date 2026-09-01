import { toast } from "sonner";
import ReportGenerator from "@/components/reports/ReportGenerator";

export default function Reports() {
  const handleGenerate = (params: {
    date_from?: string;
    date_to?: string;
    format: "csv" | "pdf" | "excel";
  }) => {
    // For CSV/EXCEL/PDF, trigger download via direct URL
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

    const url = `/api/reports/inventory?${searchParams.toString()}`;

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
        const ext =
          params.format === "csv"
            ? "csv"
            : params.format === "excel"
              ? "xlsx"
              : "pdf";
        const mimeType =
          params.format === "csv"
            ? "text/csv"
            : params.format === "excel"
              ? "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              : "application/pdf";
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
    </div>
  );
}