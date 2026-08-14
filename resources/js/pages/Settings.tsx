import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { AlertCircle } from "lucide-react";
import { toast } from "sonner";
import SettingsForm from "@/components/settings/SettingsForm";
import ProfileSection from "@/components/settings/ProfileSection";
import { CardSkeleton } from "@/components/shared/LoadingSkeleton";

export default function Settings() {
  const queryClient = useQueryClient();

  const { data: settings, isLoading, error } = useQuery({
    queryKey: ["settings"],
    queryFn: api.getSettings,
  });

  const mutation = useMutation({
    mutationFn: api.updateSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["settings"] });
      toast.success("Settings saved successfully");
    },
    onError: (err) => {
      toast.error(
        err instanceof ApiClientError ? err.message : "Failed to save settings"
      );
    },
  });

  const handleSave = (values: Record<string, string>) => {
    mutation.mutate({ settings: values });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">
          Settings
        </h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
          Configure application preferences and your profile
        </p>
      </div>

      {/* Profile Section */}
      <ProfileSection />

      {isLoading && <CardSkeleton count={3} />}

      {error && (
        <div className="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-600 dark:text-red-400">
            {(error as Error).message || "Failed to load settings"}
          </p>
        </div>
      )}

      {settings && (
        <SettingsForm
          settings={settings}
          onSave={handleSave}
          isSaving={mutation.isPending}
        />
      )}
    </div>
  );
}