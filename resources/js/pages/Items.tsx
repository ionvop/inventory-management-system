import { useState, useCallback } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Plus, Search, AlertCircle } from "lucide-react";
import { toast } from "sonner";
import ItemsTable from "@/components/items/ItemsTable";
import ItemForm from "@/components/items/ItemForm";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import Pagination from "@/components/shared/Pagination";
import EmptyState from "@/components/shared/EmptyState";
import { TableSkeleton } from "@/components/shared/LoadingSkeleton";
import type { Item, CreateItemRequest } from "@/types";

export default function Items() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [sortBy, setSortBy] = useState<string | undefined>();
  const [sortOrder, setSortOrder] = useState<"asc" | "desc" | undefined>();

  // Form state
  const [formOpen, setFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<Item | null>(null);

  // Delete state
  const [deleteItem, setDeleteItem] = useState<Item | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["items", { page, search, sortBy, sortOrder }],
    queryFn: () =>
      api.getItems({ page, limit: 10, search, sort: sortBy, order: sortOrder }),
  });

  const createMutation = useMutation({
    mutationFn: api.createItem,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Item created successfully");
      setFormOpen(false);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to create item");
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateItemRequest> }) =>
      api.updateItem(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Item updated successfully");
      setFormOpen(false);
      setEditingItem(null);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to update item");
    },
  });

  const deleteMutation = useMutation({
    mutationFn: api.deleteItem,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Item deleted successfully");
      setDeleteItem(null);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to delete item");
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

  const handleSubmit = (formData: CreateItemRequest) => {
    if (editingItem) {
      updateMutation.mutate({ id: editingItem.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleEdit = (item: Item) => {
    setEditingItem(item);
    setFormOpen(true);
  };

  const handleAdd = () => {
    setEditingItem(null);
    setFormOpen(true);
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-bold text-gray-900 dark:text-white">
            Items
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Manage your inventory items
          </p>
        </div>
        <Button onClick={handleAdd} size="sm" className="gap-1.5">
          <Plus className="w-4 h-4" />
          <span className="hidden sm:inline">Add Item</span>
        </Button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <Input
          placeholder="Search items..."
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
            {(error as Error).message || "Failed to load items"}
          </p>
        </div>
      )}

      {/* Loading */}
      {isLoading && <TableSkeleton rows={5} cols={5} />}

      {/* Empty */}
      {data && data.data.length === 0 && !isLoading && (
        <EmptyState
          title="No items found"
          description={
            search
              ? "Try adjusting your search terms"
              : "Get started by adding your first inventory item"
          }
          action={
            !search ? (
              <Button onClick={handleAdd} size="sm" className="gap-1.5">
                <Plus className="w-4 h-4" />
                Add Item
              </Button>
            ) : undefined
          }
        />
      )}

      {/* Table */}
      {data && data.data.length > 0 && (
        <>
          <ItemsTable
            items={data.data}
            onEdit={handleEdit}
            onDelete={setDeleteItem}
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

      {/* Item Form Dialog */}
      <ItemForm
        open={formOpen}
        onOpenChange={(open) => {
          setFormOpen(open);
          if (!open) setEditingItem(null);
        }}
        onSubmit={handleSubmit}
        item={editingItem}
        isSubmitting={createMutation.isPending || updateMutation.isPending}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={!!deleteItem}
        onOpenChange={(open) => {
          if (!open) setDeleteItem(null);
        }}
        title="Delete Item"
        description={`Are you sure you want to delete "${deleteItem?.name}"? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={() => deleteItem && deleteMutation.mutate(deleteItem.id)}
        variant="destructive"
      />
    </div>
  );
}