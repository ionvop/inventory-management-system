import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useUser } from "@/contexts/UserContext";
import { api, ApiClientError } from "@/lib/api";
import {
  User,
  Users,
  ArrowRight,
  Loader2,
  Package,
  AlertCircle,
  Plus,
  Pencil,
  Trash2,
  Check,
  X,
} from "lucide-react";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import type { User as UserType } from "@/types";

export default function UserSelect() {
  const { setUser, isAuthenticated } = useUser();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  // Add-user form state
  const [newUsername, setNewUsername] = useState("");
  const [addError, setAddError] = useState<string | null>(null);

  // Edit-user state
  const [editingId, setEditingId] = useState<number | null>(null);
  const [editUsername, setEditUsername] = useState("");
  const [editError, setEditError] = useState<string | null>(null);

  // Delete-user state
  const [deleting, setDeleting] = useState<UserType | null>(null);

  const {
    data: users = [],
    isLoading,
    isError,
    error,
  } = useQuery({
    queryKey: ["users"],
    queryFn: api.getUsers,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ["users"] });

  const createMutation = useMutation({
    mutationFn: api.createUser,
    onSuccess: (user) => {
      toast.success("User created successfully");
      setNewUsername("");
      setAddError(null);
      invalidate();
      // Auto-select the user they just created
      setUser(user);
      navigate("/dashboard");
    },
    onError: (err) => {
      const msg =
        err instanceof ApiClientError ? err.message : "Failed to create user";
      setAddError(msg);
      toast.error(msg);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, username }: { id: number; username: string }) =>
      api.updateUser(id, { username }),
    onSuccess: () => {
      toast.success("Username updated successfully");
      setEditingId(null);
      setEditError(null);
      invalidate();
    },
    onError: (err) => {
      const msg =
        err instanceof ApiClientError ? err.message : "Failed to update user";
      setEditError(msg);
      toast.error(msg);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: api.deleteUser,
    onSuccess: () => {
      toast.success("User deleted successfully");
      setDeleting(null);
      invalidate();
    },
    onError: (err) => {
      setDeleting(null);
      const msg =
        err instanceof ApiClientError ? err.message : "Failed to delete user";
      toast.error(msg);
    },
  });

  const handleAddUser = (e: React.FormEvent) => {
    e.preventDefault();
    const username = newUsername.trim();
    if (!username) {
      setAddError("Username is required");
      return;
    }
    setAddError(null);
    createMutation.mutate({ username });
  };

  const handleSelectUser = (user: UserType) => {
    setUser(user);
    navigate("/dashboard");
  };

  const startEdit = (user: UserType) => {
    setEditingId(user.id);
    setEditUsername(user.username);
    setEditError(null);
  };

  const submitEdit = (user: UserType) => {
    const username = editUsername.trim();
    if (!username) {
      setEditError("Username is required");
      return;
    }
    if (username === user.username) {
      setEditingId(null);
      return;
    }
    updateMutation.mutate({ id: user.id, username });
  };

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gradient-to-b from-indigo-50 to-white dark:from-gray-950 dark:to-gray-900 p-4">
      <div className="w-full max-w-md">
        {/* Logo / Brand */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-600 text-white mb-4 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <Package className="w-8 h-8" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Inventory Manager
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Select an account or create a new one
          </p>
        </div>

        {/* Loading State */}
        {isLoading && (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-8 h-8 text-indigo-500 animate-spin" />
          </div>
        )}

        {/* Error State */}
        {isError && (
          <div className="rounded-xl border border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-800 p-4 text-center">
            <p className="text-red-600 dark:text-red-400 text-sm">
              {(error as Error).message || "Failed to load users"}
            </p>
            <button
              onClick={() => window.location.reload()}
              className="mt-2 text-sm font-medium text-red-700 dark:text-red-300 underline"
            >
              Retry
            </button>
          </div>
        )}

        {/* User List */}
        {!isLoading && !isError && (
          <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            {/* Header */}
            <div className="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
              <Users className="w-4 h-4 text-indigo-500" />
              <span className="text-sm font-semibold text-gray-900 dark:text-white">
                Accounts
              </span>
              <span className="text-xs text-gray-400 dark:text-gray-600">
                ({users.length})
              </span>
            </div>

            {/* Add-user form */}
            <form onSubmit={handleAddUser} className="p-4 border-b border-gray-100 dark:border-gray-800">
              <div className="space-y-1.5">
                <Label
                  htmlFor="new-username"
                  className="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  New username
                </Label>
                <div className="flex gap-2">
                  <Input
                    id="new-username"
                    value={newUsername}
                    onChange={(e) => {
                      setNewUsername(e.target.value);
                      setAddError(null);
                    }}
                    placeholder="e.g. maria"
                    disabled={createMutation.isPending}
                  />
                  <Button
                    type="submit"
                    disabled={createMutation.isPending || !newUsername.trim()}
                    className="gap-1 flex-shrink-0"
                  >
                    {createMutation.isPending ? (
                      <Loader2 className="w-4 h-4 animate-spin" />
                    ) : (
                      <Plus className="w-4 h-4" />
                    )}
                    Add
                  </Button>
                </div>
              </div>
              {addError && (
                <div className="flex items-center gap-2 text-sm text-red-600 dark:text-red-400 mt-2">
                  <AlertCircle className="w-4 h-4 flex-shrink-0" />
                  <span>{addError}</span>
                </div>
              )}
            </form>

            {/* List of users */}
            {users.length === 0 ? (
              <div className="p-8 text-center">
                <User className="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p className="text-gray-500 dark:text-gray-400 text-sm">
                  No users yet. Add your first account above.
                </p>
              </div>
            ) : (
              <div className="divide-y divide-gray-100 dark:divide-gray-800">
                {users.map((user) => {
                  const isEditing = editingId === user.id;
                  return (
                    <div
                      key={user.id}
                      className="flex items-center gap-3 px-4 py-3"
                    >
                      <div className="flex-shrink-0 w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                        <User className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                      </div>

                      {isEditing ? (
                        <div className="flex-1 flex items-center gap-2">
                          <Input
                            value={editUsername}
                            onChange={(e) => {
                              setEditUsername(e.target.value);
                              setEditError(null);
                            }}
                            disabled={updateMutation.isPending}
                            className="h-8"
                          />
                          <Button
                            size="sm"
                            onClick={() => submitEdit(user)}
                            disabled={
                              updateMutation.isPending || !editUsername.trim()
                            }
                            className="gap-1 flex-shrink-0"
                          >
                            {updateMutation.isPending ? (
                              <Loader2 className="w-4 h-4 animate-spin" />
                            ) : (
                              <Check className="w-4 h-4" />
                            )}
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => {
                              setEditingId(null);
                              setEditError(null);
                            }}
                            className="flex-shrink-0"
                          >
                            <X className="w-4 h-4" />
                          </Button>
                        </div>
                      ) : (
                        <button
                          onClick={() => handleSelectUser(user)}
                          className="flex-1 flex items-center gap-3 text-left rounded-lg px-1 py-1 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        >
                          <span className="flex-1 font-medium text-gray-900 dark:text-white">
                            {user.username}
                          </span>
                          <ArrowRight className="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-indigo-500 transition-colors" />
                        </button>
                      )}

                      {/* Actions */}
                      {!isEditing && (
                        <div className="flex items-center gap-1">
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => startEdit(user)}
                            className="text-gray-400 hover:text-indigo-500"
                            title="Rename"
                          >
                            <Pencil className="w-4 h-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setDeleting(user)}
                            className="text-gray-400 hover:text-red-500"
                            title="Delete"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}
      </div>

      <p className="mt-8 text-xs text-gray-400 dark:text-gray-600">
        Inventory Management System v1.0
      </p>

      {/* Delete confirmation */}
      <ConfirmDialog
        open={!!deleting}
        onOpenChange={(open) => !open && setDeleting(null)}
        title="Delete user"
        description={
          deleting
            ? `Are you sure you want to delete "${deleting.username}"? This cannot be undone.`
            : ""
        }
        confirmLabel="Delete"
        variant="destructive"
        onConfirm={() => deleting && deleteMutation.mutate(deleting.id)}
      />
    </div>
  );
}