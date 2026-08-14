import { useState } from "react";
import { useUser } from "@/contexts/UserContext";
import { useMutation } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { User, Save, Loader2, AlertCircle } from "lucide-react";
import { toast } from "sonner";

export default function ProfileSection() {
  const { user, setUser } = useUser();
  const [username, setUsername] = useState(user?.username ?? "");
  const [error, setError] = useState<string | null>(null);

  const mutation = useMutation({
    mutationFn: (newUsername: string) => {
      if (!user) throw new Error("Not authenticated");
      return api.updateUser(user.id, { username: newUsername });
    },
    onSuccess: (updatedUser) => {
      setUser(updatedUser);
      toast.success("Username updated successfully");
      setError(null);
    },
    onError: (err) => {
      const msg =
        err instanceof ApiClientError
          ? err.message
          : "Failed to update username";
      setError(msg);
      toast.error(msg);
    },
  });

  const handleSave = () => {
    if (!username.trim()) {
      setError("Username cannot be empty");
      return;
    }
    if (username.trim() === user?.username) {
      setError("No changes detected");
      return;
    }
    mutation.mutate(username.trim());
  };

  if (!user) {
    return (
      <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-6 text-center">
        <User className="w-8 h-8 text-gray-400 mx-auto mb-2" />
        <p className="text-sm text-gray-500 dark:text-gray-400">
          You need to be logged in to view your profile.
        </p>
      </div>
    );
  }

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 overflow-hidden">
      <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
        <h3 className="font-semibold text-gray-900 dark:text-white text-sm">
          Profile
        </h3>
      </div>
      <div className="p-4 space-y-4">
        <div className="flex items-center gap-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50">
          <div className="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
            <User className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
          </div>
          <div>
            <p className="font-medium text-gray-900 dark:text-white">
              {user.username}
            </p>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Current username
            </p>
          </div>
        </div>

        <div className="space-y-1.5">
          <Label className="text-sm font-medium text-gray-700 dark:text-gray-300">
            New Username
          </Label>
          <Input
            value={username}
            onChange={(e) => {
              setUsername(e.target.value);
              setError(null);
            }}
            placeholder="Enter new username"
            disabled={mutation.isPending}
          />
          {error && (
            <div className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400 mt-1">
              <AlertCircle className="w-4 h-4" />
              {error}
            </div>
          )}
        </div>

        <div className="flex justify-end">
          <Button
            onClick={handleSave}
            disabled={mutation.isPending || !username.trim()}
            className="gap-2"
          >
            {mutation.isPending ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <Save className="w-4 h-4" />
            )}
            {mutation.isPending ? "Saving..." : "Save Username"}
          </Button>
        </div>
      </div>
    </div>
  );
}