import { Form } from '@inertiajs/react';

export interface SupplierItem {
    id: number;
    supplier_id: number;
    supplier_name: string | null;
    item_id: number;
    item_code: string | null;
    item_description: string | null;
    price: string;
    effective_date: string;
    active: boolean;
    has_transactions: boolean;
}

export interface SupplierOption {
    id: number;
    name: string;
}

export interface ItemOption {
    id: number;
    code: string;
    description: string;
}

interface SupplierItemFormProps {
    mode: 'create' | 'edit';
    supplierItem?: SupplierItem;
    suppliers: SupplierOption[];
    items: ItemOption[];
    onCancel?: () => void;
}

export default function SupplierItemForm({
    mode,
    supplierItem,
    suppliers,
    items,
    onCancel,
}: SupplierItemFormProps) {
    const isEdit = mode === 'edit';

    return (
        <Form
            action={
                isEdit
                    ? `/supplier-items/${supplierItem?.id}`
                    : '/supplier-items'
            }
            method={isEdit ? 'patch' : 'post'}
        >
            {({ errors, processing }) => (
                <div className="grid gap-3 sm:grid-cols-4">
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Supplier
                        </label>
                        {isEdit ? (
                            <>
                                <p className="rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-foreground">
                                    {supplierItem?.supplier_name ?? '—'}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Supplier and item cannot be changed. Add a
                                    new record instead.
                                </p>
                            </>
                        ) : (
                            <select
                                name="supplier_id"
                                defaultValue=""
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                            >
                                <option value="" disabled>
                                    Select a supplier
                                </option>
                                {suppliers.map((supplier) => (
                                    <option
                                        key={supplier.id}
                                        value={supplier.id}
                                    >
                                        {supplier.name}
                                    </option>
                                ))}
                            </select>
                        )}
                        {errors.supplier_id && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.supplier_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Item
                        </label>
                        {isEdit ? (
                            <p className="rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-foreground">
                                {supplierItem?.item_code ?? '—'}
                                {supplierItem?.item_description
                                    ? ` — ${supplierItem.item_description}`
                                    : ''}
                            </p>
                        ) : (
                            <select
                                name="item_id"
                                defaultValue=""
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                            >
                                <option value="" disabled>
                                    Select an item
                                </option>
                                {items.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.code} — {item.description}
                                    </option>
                                ))}
                            </select>
                        )}
                        {errors.item_id && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.item_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Contract price
                        </label>
                        {isEdit ? (
                            <>
                                <p className="rounded-md border border-border bg-muted/50 px-3 py-2 text-sm text-foreground">
                                    {supplierItem?.price}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Price is immutable. Add a new record to
                                    change it.
                                </p>
                            </>
                        ) : (
                            <input
                                type="number"
                                name="price"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                            />
                        )}
                        {errors.price && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.price}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Effective date
                        </label>
                        <input
                            type="date"
                            name="effective_date"
                            defaultValue={supplierItem?.effective_date}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.effective_date && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.effective_date}
                            </p>
                        )}
                    </div>

                    <div className="flex items-end gap-2 sm:col-span-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing
                                ? isEdit
                                    ? 'Saving...'
                                    : 'Adding...'
                                : isEdit
                                  ? 'Save changes'
                                  : 'Add contract price'}
                        </button>
                        {isEdit && onCancel && (
                            <button
                                type="button"
                                onClick={onCancel}
                                className="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                </div>
            )}
        </Form>
    );
}
