import { useEffect, useMemo, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { AlertCircle } from "lucide-react";
import type { Transaction, MovementType } from "@/types";

const transactionSchema = z.object({
  item_id: z.coerce.number().min(1, "Item is required"),
  movement: z.enum(["in", "out"], {
    required_error: "Movement type is required",
  }),
  quantity: z.coerce.number().min(0.01, "Must be greater than 0"),
});

type TransactionFormValues = z.infer<typeof transactionSchema>;

interface TransactionFormProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (data: TransactionFormValues) => void;
  transaction?: Transaction | null;
  isSubmitting?: boolean;
  serverError?: string | null;
}

export default function TransactionForm({
  open,
  onOpenChange,
  onSubmit,
  transaction,
  isSubmitting,
  serverError,
}: TransactionFormProps) {
  const [movementType, setMovementType] = useState<MovementType>("in");

  const { data: itemsData } = useQuery({
    queryKey: ["items", { limit: 100 }],
    queryFn: () => api.getItems({ limit: 100 }),
    enabled: open,
  });

  const form = useForm<TransactionFormValues>({
    resolver: zodResolver(transactionSchema),
    defaultValues: {
      item_id: 0,
      movement: "in",
      quantity: 1,
    },
  });

  useEffect(() => {
    if (open) {
      if (transaction) {
        form.reset({
          item_id: transaction.item_id,
          movement: transaction.movement,
          quantity: transaction.quantity,
        });
        setMovementType(transaction.movement);
      } else {
        form.reset({
          item_id: 0,
          movement: "in",
          quantity: 1,
        });
        setMovementType("in");
      }
    }
  }, [open, transaction, form]);

  const watchedItemId = form.watch("item_id");
  const watchedQuantity = form.watch("quantity");

  const selectedItem = useMemo(
    () => itemsData?.data.find((item) => item.id === watchedItemId) ?? null,
    [itemsData, watchedItemId]
  );

  const stockPreview = useMemo(() => {
    if (!selectedItem) return null;

    // In edit mode, back out the transaction's own effect so the preview
    // reflects the stock level before this transaction is applied.
    const baseStock = transaction
      ? selectedItem.current_stock -
        (transaction.movement === "in"
          ? transaction.quantity
          : -transaction.quantity)
      : selectedItem.current_stock;

    const quantity = Number(watchedQuantity);
    if (!Number.isFinite(quantity) || quantity <= 0) return null;

    const delta = movementType === "in" ? quantity : -quantity;
    const projectedStock = baseStock + delta;

    return {
      currentStock: selectedItem.current_stock,
      baseStock,
      projectedStock,
      delta,
    };
  }, [selectedItem, transaction, watchedQuantity, movementType]);

  const isInsufficientStock =
    stockPreview !== null &&
    movementType === "out" &&
    stockPreview.projectedStock < 0;

  const isLowStock =
    stockPreview !== null &&
    stockPreview.projectedStock < selectedItem!.minimum_stock;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>
            {transaction ? "Edit Transaction" : "Add Transaction"}
          </DialogTitle>
          <DialogDescription>
            {transaction
              ? "Update the transaction details below."
              : "Record a new stock movement."}
          </DialogDescription>
        </DialogHeader>

        {serverError && (
          <div className="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-3 flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-red-500 flex-shrink-0" />
            <p className="text-sm text-red-600 dark:text-red-400">
              {serverError}
            </p>
          </div>
        )}

        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="space-y-4"
          >
            <FormField
              control={form.control}
              name="item_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Item</FormLabel>
                  <Select
                    onValueChange={(v) => field.onChange(parseInt(v))}
                    value={field.value ? String(field.value) : undefined}
                    disabled={!!transaction}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="Select an item" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {itemsData?.data.map((item) => (
                        <SelectItem key={item.id} value={String(item.id)}>
                          {item.name} ({item.current_stock} {item.unit})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="movement"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Movement Type</FormLabel>
                  <Select
                    onValueChange={(v: MovementType) => {
                      field.onChange(v);
                      setMovementType(v);
                    }}
                    value={field.value}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="in">Stock In (+)</SelectItem>
                      <SelectItem value="out">Stock Out (−)</SelectItem>
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="quantity"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Quantity</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      min="0.01"
                      step="any"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {stockPreview && (
              <div className="rounded-lg border border-border bg-muted/50 p-3 space-y-2">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-muted-foreground">
                    {transaction ? "Stock before" : "Current stock"}
                  </span>
                  <span className="font-medium tabular-nums">
                    {stockPreview.baseStock} {selectedItem?.unit}
                  </span>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-muted-foreground">
                    {movementType === "in" ? "Stock in (+)" : "Stock out (−)"}
                  </span>
                  <span className="font-medium tabular-nums">
                    {movementType === "in" ? "+" : "−"}
                    {stockPreview.delta} {selectedItem?.unit}
                  </span>
                </div>
                <div className="flex items-center justify-between text-sm border-t border-border pt-2">
                  <span className="font-medium">Resulting stock</span>
                  <span
                    className={`font-semibold tabular-nums ${
                      isInsufficientStock
                        ? "text-red-600 dark:text-red-400"
                        : isLowStock
                        ? "text-amber-600 dark:text-amber-400"
                        : "text-foreground"
                    }`}
                  >
                    {stockPreview.projectedStock} {selectedItem?.unit}
                  </span>
                </div>
              </div>
            )}

            {isInsufficientStock && (
              <div className="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/20 p-3 flex items-center gap-2">
                <AlertCircle className="w-4 h-4 text-red-500 flex-shrink-0" />
                <p className="text-sm text-red-600 dark:text-red-400">
                  Not enough stock for this transaction. Current stock is{" "}
                  {stockPreview?.baseStock} {selectedItem?.unit}.
                </p>
              </div>
            )}

            {isLowStock && !isInsufficientStock && (
              <div className="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20 p-3 flex items-center gap-2">
                <AlertCircle className="w-4 h-4 text-amber-500 flex-shrink-0" />
                <p className="text-sm text-amber-600 dark:text-amber-400">
                  Resulting stock will be below the minimum of{" "}
                  {selectedItem?.minimum_stock} {selectedItem?.unit}.
                </p>
              </div>
            )}

            <div className="flex justify-end gap-3 pt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => onOpenChange(false)}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                disabled={isSubmitting}
                className={
                  movementType === "out"
                    ? "bg-rose-600 hover:bg-rose-700"
                    : ""
                }
              >
                {isSubmitting
                  ? "Saving..."
                  : transaction
                  ? "Update"
                  : movementType === "out"
                  ? "Record Out"
                  : "Record In"}
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}