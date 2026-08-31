import { useState, useCallback } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Plus, Search, AlertCircle } from "lucide-react";
import { toast } from "sonner";
import UsersTable from "@/components/users/UsersTable";
import UserForm from "@/components/users/UserForm";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import Pagination from "@/components/shared/Pagination";
import EmptyState from "@/components/shared/EmptyState";
import { TableSkeleton } from "@/components/shared/LoadingSkeleton";
import type { User, CreateUserRequest } from "@/types";

export default function Users() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState<string | undefined>();
  const [sortOrder, setSortOrder] = useState<"asc" | "desc" | undefined>();

  // Form state
  const [formOpen, setFormOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  // Delete state
  const [deleteUser, setDeleteUser] = useState<User | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["users", { page, search, sortBy, sortOrder }],
    queryFn: () =>
      api.getUsersPaginated({ page, limit: 10, search, sort: sortBy, order: sortOrder }),
  });

  const createMutation = useMutation({
    mutationFn: api.createUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast.success("User created successfully");
      setFormOpen(false);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to create user");
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateUserRequest> }) =>
      api.updateUser(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast.success("User updated successfully");
      setFormOpen(false);
      setEditingUser(null);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to update user");
    },
  });

  const deleteMutation = useMutation({
    mutationFn: api.deleteUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
      toast.success("User deleted successfully");
      setDeleteUser(null);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to delete user");
    },
  });

  const handleSort = useCallback(
    (column: string) => {
      if (sortBy === column) {
        if (sortOrder === "asc") {
          setSortOrder("desc");
        } else if (sortOrder === "desc") {
          setSortBy(undefined);
          setSortOrder(undefined);
        }
      } else {
        setSortBy(column);
        setSortOrder("asc");
      }
    },
    [sortBy, sortOrder]
  );

  const handleSubmit = (formData: CreateUserRequest) => {
    if (editingUser) {
      updateMutation.mutate({ id: editingUser.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleEdit = (user: User) => {
    setEditingUser(user);
    setFormOpen(true);
  };

  const handleAdd = () => {
    setEditingUser(null);
    setFormOpen(true);
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-bold text-gray-900 dark:text-white">
            Users
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Manage your users
          </p>
        </div>
        <Button onClick={handleAdd} size="sm" className="gap-1.5">
          <Plus className="w-4 h-4" />
          <span className="hidden sm:inline">Add User</span>
        </Button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <Input
          placeholder="Search users..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-9"
        />
      </div>

      {/* Error */}
      {error && (
        <div className="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-600 dark:text-red-400">
            {(error as Error).message || "Failed to load users"}
          </p>
        </div>
      )}

      {/* Loading */}
      {isLoading && <TableSkeleton rows={5} cols={3} />}

      {/* Empty */}
      {data && data.data.length === 0 && !isLoading && (
        <EmptyState
          title="No users found"
          description={
            search
              ? "Try adjusting your search terms"
              : "Get started by adding your first user"
          }
          action={
            !search ? (
              <Button onClick={handleAdd} size="sm" className="gap-1.5">
                <Plus className="w-4 h-4" />
                Add User
              </Button>
            ) : undefined
          }
        />
      )}

      {/* Table */}
      {data && data.data.length > 0 && (
        <>
          <UsersTable
            users={data.data}
            onEdit={handleEdit}
            onDelete={setDeleteUser}
            sortBy={sortBy}
            sortOrder={sortOrder}
            onSort={handleSort}
          />
          <Pagination
            currentPage={data.pagination.page}
            lastPage={data.pagination.total_pages}
            onPageChange={setPage}
          />
        </>
      )}

      {/* User Form Dialog */}
      <UserForm
        open={formOpen}
        onOpenChange={(open) => {
          setFormOpen(open);
          if (!open) setEditingUser(null);
        }}
        onSubmit={handleSubmit}
        user={editingUser}
        isSubmitting={createMutation.isPending || updateMutation.isPending}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={!!deleteUser}
        onOpenChange={(open) => {
          if (!open) setDeleteUser(null);
        }}
        title="Delete User"
        description={`Are you sure you want to delete "${deleteUser?.username}"? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={() => deleteUser && deleteMutation.mutate(deleteUser.id)}
        variant="destructive"
      />
    </div>
  );
}