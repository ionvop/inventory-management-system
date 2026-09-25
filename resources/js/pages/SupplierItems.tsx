import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import SupplierItemForm, {
    type ItemOption,
    type SupplierItem,
    type SupplierOption,
} from '@/components/supplier-item-form';
import AppLayout from '@/layouts/app-layout';

interface SupplierItemsProps {
    supplierItems: SupplierItem[];
    suppliers: SupplierOption[];
    items: ItemOption[];
}

export default function SupplierItems({
    supplierItems,
    suppliers,
    items,
}: SupplierItemsProps) {
    const [editing, setEditing] = useState<SupplierItem | null>(null);
    const [confirmingDelete, setConfirmingDelete] =
        useState<SupplierItem | null>(null);

    const canAdd = suppliers.length > 0 && items.length > 0;

    return (
        <AppLayout title="Supplier Items">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-bold text-foreground">
                        Supplier Items
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Contract prices linking a supplier to an item. Changing
                        a price adds a new dated record so past transactions
                        keep their original cost.
                    </p>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Supplier</th>
                            <th className="px-4 py-3 font-medium">Item</th>
                            <th className="px-4 py-3 font-medium">
                                Contract price
                            </th>
                            <th className="px-4 py-3 font-medium">
                                Effective date
                            </th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {supplierItems.length === 0 && (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No contract prices yet. Add the first one
                                    below.
                                </td>
                            </tr>
                        )}
                        {supplierItems.map((supplierItem) => (
                            <tr
                                key={supplierItem.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {supplierItem.supplier_name ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {supplierItem.item_code ?? '—'}
                                    {supplierItem.item_description
                                        ? ` — ${supplierItem.item_description}`
                                        : ''}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {supplierItem.price}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {supplierItem.effective_date}
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={
                                            supplierItem.active
                                                ? 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                                                : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                                        }
                                    >
                                        {supplierItem.active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Form
                                            action={`/supplier-items/${supplierItem.id}`}
                                            method="patch"
                                        >
                                            <input
                                                type="hidden"
                                                name="effective_date"
                                                value={
                                                    supplierItem.effective_date
                                                }
                                            />
                                            <input
                                                type="hidden"
                                                name="active"
                                                value={
                                                    supplierItem.active
                                                        ? '0'
                                                        : '1'
                                                }
                                            />
                                            <button
                                                type="submit"
                                                className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                            >
                                                {supplierItem.active
                                                    ? 'Deactivate'
                                                    : 'Activate'}
                                            </button>
                                        </Form>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setEditing(supplierItem);
                                                setConfirmingDelete(null);
                                            }}
                                            className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            disabled={
                                                supplierItem.has_transactions
                                            }
                                            title={
                                                supplierItem.has_transactions
                                                    ? 'This record has transactions and cannot be deleted. Deactivate it instead.'
                                                    : undefined
                                            }
                                            onClick={() => {
                                                setConfirmingDelete(
                                                    supplierItem,
                                                );
                                                setEditing(null);
                                            }}
                                            className="rounded-md border border-border px-2 py-1 text-xs text-destructive hover:bg-destructive/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {confirmingDelete && (
                <div className="mt-4 rounded-lg border border-destructive/30 bg-destructive/10 p-4">
                    <p className="text-sm text-destructive">
                        Delete the contract price for{' '}
                        {confirmingDelete.item_code ?? 'this item'} from{' '}
                        {confirmingDelete.supplier_name ?? 'this supplier'}?
                        This cannot be undone.
                    </p>
                    <div className="mt-3 flex gap-2">
                        <Form
                            action={`/supplier-items/${confirmingDelete.id}`}
                            method="delete"
                        >
                            {({ processing }) => (
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md bg-destructive px-3 py-1.5 text-xs font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                                >
                                    {processing ? 'Deleting...' : 'Delete'}
                                </button>
                            )}
                        </Form>
                        <button
                            type="button"
                            onClick={() => setConfirmingDelete(null)}
                            className="rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            )}

            <div className="mt-8 rounded-lg border border-border bg-card p-4">
                <div className="mb-3 flex items-center justify-between">
                    <h2 className="text-sm font-semibold text-foreground">
                        {editing ? 'Edit contract price' : 'Add contract price'}
                    </h2>
                    {editing && (
                        <button
                            type="button"
                            onClick={() => setEditing(null)}
                            className="text-xs text-muted-foreground hover:text-foreground"
                        >
                            Cancel
                        </button>
                    )}
                </div>
                {canAdd || editing ? (
                    <SupplierItemForm
                        key={editing?.id ?? 'create'}
                        mode={editing ? 'edit' : 'create'}
                        supplierItem={editing ?? undefined}
                        suppliers={suppliers}
                        items={items}
                        onCancel={() => setEditing(null)}
                    />
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Add at least one active{' '}
                        <Link
                            href="/suppliers"
                            className="text-primary hover:underline"
                        >
                            supplier
                        </Link>{' '}
                        and one active{' '}
                        <Link
                            href="/items"
                            className="text-primary hover:underline"
                        >
                            item
                        </Link>{' '}
                        before setting a contract price.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
