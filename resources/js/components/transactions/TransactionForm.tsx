import { useEffect, useState } from "react";
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