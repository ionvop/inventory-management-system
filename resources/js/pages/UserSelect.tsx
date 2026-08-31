import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { useUser } from "@/contexts/UserContext";
import { api, ApiClientError } from "@/lib/api";
import { User, Users, ArrowRight, Loader2, Package, AlertCircle } from "lucide-react";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import type { User as UserType } from "@/types";

export default function UserSelect() {
  const [users, setUsers] = useState<UserType[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [newUsername, setNewUsername] = useState("");
  const [createError, setCreateError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const { setUser, isAuthenticated } = useUser();
  const navigate = useNavigate();

  useEffect(() => {
    if (isAuthenticated) {
      navigate("/dashboard", { replace: true });
      return;
    }

    api
      .getUsers()
      .then(setUsers)
      .catch((err) => setError(err.message || "Failed to load users"))
      .finally(() => setLoading(false));
  }, [isAuthenticated, navigate]);

  const handleSelectUser = (user: UserType) => {
    setUser(user);
    navigate("/dashboard");
  };

  const handleCreateFirstUser = async (e: React.FormEvent) => {
    e.preventDefault();
    const username = newUsername.trim();
    if (!username) {
      setCreateError("Username is required");
      return;
    }
    setCreating(true);
    setCreateError(null);
    try {
      const user = await api.createUser({ username });
      toast.success("Welcome! Your account has been created.");
      setUser(user);
      navigate("/dashboard");
    } catch (err) {
      const msg =
        err instanceof ApiClientError ? err.message : "Failed to create account";
      setCreateError(msg);
      toast.error(msg);
    } finally {
      setCreating(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-indigo-50 to-white dark:from-gray-950 dark:to-gray-900 p-4">
      <div className="w-full max-w-md">
        {/* Logo / Brand */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 text-white mb-4 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <Package className="w-8 h-8" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Inventory Manager
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Select your account to continue
          </p>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 text-indigo-500 animate-spin" />
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-800 p-4 text-center">
            <p className="text-red-600 dark:text-red-400 text-sm">{error}</p>
            <button
              onClick={() => window.location.reload()}
              className="mt-2 text-sm font-medium text-red-700 dark:text-red-300 underline"
            >
              Retry
            </button>
          </div>
        )}

        {/* User List */}
        {!loading && !error && (
          <>
            {users.length === 0 ? (
              <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-8">
                <div className="text-center mb-6">
                  <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 mb-3">
                    <Users className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                  </div>
                  <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                    Get started
                  </h2>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    No accounts exist yet. Create the first user to begin.
                  </p>
                </div>

                <form onSubmit={handleCreateFirstUser} className="space-y-4">
                  <div className="space-y-1.5">
                    <Label
                      htmlFor="first-username"
                      className="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                      Username
                    </Label>
                    <Input
                      id="first-username"
                      value={newUsername}
                      onChange={(e) => {
                        setNewUsername(e.target.value);
                        setCreateError(null);
                      }}
                      placeholder="e.g. maria"
                      disabled={creating}
                    />
                  </div>

                  {createError && (
                    <div className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                      <AlertCircle className="w-4 h-4 flex-shrink-0" />
                      <span>{createError}</span>
                    </div>
                  )}

                  <Button
                    type="submit"
                    className="w-full gap-2"
                    disabled={creating || !newUsername.trim()}
                  >
                    {creating ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <User className="w-4 h-4" />
                    )}
                    {creating ? "Creating..." : "Create Account"}
                  </Button>
                </form>
              </div>
            ) : (
              <div className="space-y-3">
                {users.map((user) => (
                  <button
                    key={user.id}
                    onClick={() => handleSelectUser(user)}
                    className="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md hover:shadow-indigo-100 dark:hover:shadow-indigo-900/20 transition-all duration-200 group"
                  >
                    <div className="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                      <User className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div className="flex-1 text-left">
                      <p className="font-medium text-gray-900 dark:text-white">
                        {user.username}
                      </p>
                    </div>
                    <ArrowRight className="w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors" />
                  </button>
                ))}
              </div>
            )}
          </>
        )}
      </div>

      <p className="mt-8 text-xs text-gray-400 dark:text-gray-600">
        Inventory Management System v1.0
      </p>
    </div>
  );
}