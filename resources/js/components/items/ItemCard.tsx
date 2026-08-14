import { Pencil, Trash2, AlertTriangle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { Item } from "@/types";

interface ItemCardProps {
  item: Item;
  onEdit: (item: Item) => void;
  onDelete: (item: Item) => void;
}

export default function ItemCard({ item, onEdit, onDelete }: ItemCardProps) {
  const isLowStock = item.current_stock <= item.minimum_stock;

  return (
    <div className="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4">
      <div className="flex items-start justify-between gap-2">
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <h3 className="font-semibold text-gray-900 dark:text-white truncate">
              {item.name}
            </h3>
            {isLowStock && (
              <AlertTriangle className="w-4 h-4 text-amber-500 flex-shrink-0" />
            )}
          </div>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {item.unit}
          </p>
        </div>
        <div className="flex items-center gap-1 flex-shrink-0">
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={() => onEdit(item)}
          >
            <Pencil className="w-3.5 h-3.5" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 text-red-500 hover:text-red-600"
            onClick={() => onDelete(item)}
          >
            <Trash2 className="w-3.5 h-3.5" />
          </Button>
        </div>
      </div>
      <div className="flex items-center gap-3 mt-3">
        <div>
          <p className="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
            {item.current_stock}
          </p>
          <p className="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Current
          </p>
        </div>
        <div className="w-px h-8 bg-gray-200 dark:bg-gray-700" />
        <div>
          <p className="text-lg font-semibold text-gray-600 dark:text-gray-400 tabular-nums">
            {item.minimum_stock}
          </p>
          <p className="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Minimum
          </p>
        </div>
        {isLowStock && (
          <Badge className="ml-auto bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 text-xs">
            Low Stock
          </Badge>
        )}
      </div>
    </div>
  );
}