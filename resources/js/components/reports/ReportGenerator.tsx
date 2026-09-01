import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { FileSpreadsheet, FileText, Download } from "lucide-react";

interface ReportGeneratorProps {
  onGenerate: (params: {
    date_from?: string;
    date_to?: string;
    format: "csv" | "pdf" | "excel";
  }) => void;
  isGenerating: boolean;
}

export default function ReportGenerator({
  onGenerate,
  isGenerating,
}: ReportGeneratorProps) {
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const [format, setFormat] = useState<"csv" | "pdf" | "excel">("excel");

  const handleGenerate = () => {
    onGenerate({
      date_from: startDate || undefined,
      date_to: endDate || undefined,
      format,
    });
  };

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4 sm:p-6">
      <h2 className="font-semibold text-gray-900 dark:text-white mb-4">
        Generate Report
      </h2>
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="space-y-1.5">
          <Label className="text-xs text-gray-500 dark:text-gray-400">
            Start Date
          </Label>
          <Input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
          />
        </div>
        <div className="space-y-1.5">
          <Label className="text-xs text-gray-500 dark:text-gray-400">
            End Date
          </Label>
          <Input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
          />
        </div>
        <div className="space-y-1.5">
          <Label className="text-xs text-gray-500 dark:text-gray-400">
            Format
          </Label>
          <Select
            value={format}
            onValueChange={(v) => setFormat(v as "csv" | "pdf" | "excel")}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="excel">
                <span className="flex items-center gap-2">
                  <FileSpreadsheet className="w-4 h-4" /> EXCEL
                </span>
              </SelectItem>
              <SelectItem value="csv">
                <span className="flex items-center gap-2">
                  <FileSpreadsheet className="w-4 h-4" /> CSV
                </span>
              </SelectItem>
              <SelectItem value="pdf">
                <span className="flex items-center gap-2">
                  <FileText className="w-4 h-4" /> PDF
                </span>
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>
      <Button
        onClick={handleGenerate}
        disabled={isGenerating}
        className="mt-4 w-full sm:w-auto gap-2"
      >
        <Download className="w-4 h-4" />
        {isGenerating ? "Generating..." : "Generate Report"}
      </Button>
    </div>
  );
}