import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Save, Loader2 } from "lucide-react";

interface SettingsFormProps {
  settings: Record<string, string>;
  onSave: (settings: Record<string, string>) => void;
  isSaving: boolean;
}

export default function SettingsForm({
  settings,
  onSave,
  isSaving,
}: SettingsFormProps) {
  const [values, setValues] = useState<Record<string, string>>({});

  useEffect(() => {
    setValues({ ...settings });
  }, [settings]);

  const handleChange = (key: string, value: string) => {
    setValues((prev) => ({ ...prev, [key]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(values);
  };

  const entries = Object.entries(values);

  if (entries.length === 0) {
    return (
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-8 text-center">
        <p className="text-sm text-gray-500 dark:text-gray-400">
          No settings available
        </p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
        <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
          <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
            Application Settings
          </h3>
        </div>
        <div className="p-4 space-y-4">
          {entries.map(([key, value]) => (
            <div key={key} className="space-y-1.5">
              <Label className="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">
                {key.replace(/_/g, " ")}
              </Label>
              <Input
                value={value}
                onChange={(e) => handleChange(key, e.target.value)}
                placeholder={`Enter ${key.replace(/_/g, " ")}`}
              />
              <p className="text-[11px] text-gray-400 dark:text-gray-500">
                Key: {key}
              </p>
            </div>
          ))}
        </div>
      </div>

      <Button type="submit" disabled={isSaving} className="gap-2">
        {isSaving ? (
          <Loader2 className="w-4 h-4 animate-spin" />
        ) : (
          <Save className="w-4 h-4" />
        )}
        {isSaving ? "Saving..." : "Save Settings"}
      </Button>
    </form>
  );
}