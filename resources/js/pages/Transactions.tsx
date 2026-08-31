import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, ApiClientError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Plus, AlertCircle, Filter } from "lucide-react";
import { toast } from "sonner";
import TransactionsTable from "@/components/transactions/TransactionsTable";
import TransactionForm from "@/components/transactions/TransactionForm";
import ItemSelectCombobox from "@/components/transactions/ItemSelectCombobox";
import ConfirmDialog from "@/components/shared/ConfirmDialog";
import Pagination from "@/components/shared/Pagination";
import EmptyState from "@/components/shared/EmptyState";
import { TableSkeleton } from "@/components/shared/LoadingSkeleton";
import type { Transaction, MovementType, CreateTransactionRequest } from "@/types";

export default function Transactions() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [filterMovement, setFilterMovement] = useState<MovementType | "all">("all");

  // Form state
  const [formOpen, setFormOpen] = useState(false);
  const [editingTransaction, setEditingTransaction] = useState<Transaction | null>(null);
  const [serverError, setServerError] = useState<string | null>(null);

  // Delete state
  const [deleteTx, setDeleteTx] = useState<Transaction | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["transactions", { page, item_id: selectedItemId, movement: filterMovement }],
    queryFn: () =>
      api.getTransactions({
        page,
        limit: 10,
        item_id: selectedItemId ?? undefined,
        movement: filterMovement === "all" ? undefined : filterMovement,
      }),
  });

  const createMutation = useMutation({
    mutationFn: api.createTransaction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Transaction recorded successfully");
      setFormOpen(false);
      setServerError(null);
    },
    onError: (err) => {
      const msg = err instanceof ApiClientError ? err.message : "Failed to record transaction";
      setServerError(msg);
      toast.error(msg);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<CreateTransactionRequest> }) =>
      api.updateTransaction(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Transaction updated successfully");
      setFormOpen(false);
      setEditingTransaction(null);
      setServerError(null);
    },
    onError: (err) => {
      const msg = err instanceof ApiClientError ? err.message : "Failed to update transaction";
      setServerError(msg);
      toast.error(msg);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: api.deleteTransaction,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["transactions"] });
      queryClient.invalidateQueries({ queryKey: ["items"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-summary"] });
      toast.success("Transaction deleted successfully");
      setDeleteTx(null);
    },
    onError: (err) => {
      toast.error(err instanceof ApiClientError ? err.message : "Failed to delete transaction");
    },
  });

  const handleSubmit = (formData: CreateTransactionRequest) => {
    setServerError(null);
    if (editingTransaction) {
      updateMutation.mutate({ id: editingTransaction.id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleEdit = (tx: Transaction) => {
    setEditingTransaction(tx);
    setServerError(null);
    setFormOpen(true);
  };

  const handleAdd = () => {
    setEditingTransaction(null);
    setServerError(null);
    setFormOpen(true);
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-xl font-bold text-gray-900 dark:text-white">
            Transactions
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Stock movement history
          </p>
        </div>
        <Button onClick={handleAdd} size="sm" className="gap-1.5">
          <Plus className="w-4 h-4" />
          <span className="hidden sm:inline">Add Transaction</span>
        </Button>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-2">
        <div className="flex-1">
          <ItemSelectCombobox
            value={selectedItemId}
            onChange={(id) => {
              setSelectedItemId(id);
              setPage(1);
            }}
          />
        </div>
        <Select
          value={filterMovement}
          onValueChange={(v) => {
            setFilterMovement(v as MovementType | "all");
            setPage(1);
          }}
        >
          <SelectTrigger className="w-full sm:w-40">
            <Filter className="w-4 h-4 mr-1.5" />
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Types</SelectItem>
            <SelectItem value="in">Stock In</SelectItem>
            <SelectItem value="out">Stock Out</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Error */}
      {error && (
        <div className="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-4 flex items-center gap-3">
          <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0" />
          <p className="text-sm text-red-600 dark:text-red-400">
            {(error as Error).message || "Failed to load transactions"}
          </p>
        </div>
      )}

      {/* Loading */}
      {isLoading && <TableSkeleton rows={5} cols={6} />}

      {/* Empty */}
      {data && data.data.length === 0 && !isLoading && (
        <EmptyState
          title="No transactions found"
          description={
            selectedItemId || filterMovement !== "all"
              ? "Try adjusting your filters"
              : "Get started by recording your first transaction"
          }
          action={
            !selectedItemId && filterMovement === "all" ? (
              <Button onClick={handleAdd} size="sm" className="gap-1.5">
                <Plus className="w-4 h-4" />
                Add Transaction
              </Button>
            ) : undefined
          }
        />
      )}

      {/* Table */}
      {data && data.data.length > 0 && (
        <>
          <TransactionsTable
            transactions={data.data}
            onEdit={handleEdit}
            onDelete={setDeleteTx}
          />
          <Pagination
            currentPage={data.pagination.page}
            lastPage={data.pagination.total_pages}
            onPageChange={setPage}
          />
        </>
      )}

      {/* Transaction Form Dialog */}
      <TransactionForm
        open={formOpen}
        onOpenChange={(open) => {
          setFormOpen(open);
          if (!open) {
            setEditingTransaction(null);
            setServerError(null);
          }
        }}
        onSubmit={handleSubmit}
        transaction={editingTransaction}
        isSubmitting={createMutation.isPending || updateMutation.isPending}
        serverError={serverError}
      />

      {/* Delete Confirmation */}
      <ConfirmDialog
        open={!!deleteTx}
        onOpenChange={(open) => {
          if (!open) setDeleteTx(null);
        }}
        title="Delete Transaction"
        description="Are you sure you want to delete this transaction? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={() => deleteTx && deleteMutation.mutate(deleteTx.id)}
        variant="destructive"
      />
    </div>
  );
}