import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import ItemForm, { type Item } from '@/components/item-form';
import AppLayout from '@/layouts/app-layout';

interface ItemsProps {
    items: Item[];
}

export default function Items({ items }: ItemsProps) {
    const [editing, setEditing] = useState<Item | null>(null);
    const [confirmingDelete, setConfirmingDelete] = useState<Item | null>(null);

    return (
        <AppLayout title="Items">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-bold text-foreground">Items</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Nutrition formula products tracked in inventory.
                    </p>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Code</th>
                            <th className="px-4 py-3 font-medium">
                                Description
                            </th>
                            <th className="px-4 py-3 font-medium">Unit</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No items yet. Add the first one below.
                                </td>
                            </tr>
                        )}
                        {items.map((item) => (
                            <tr
                                key={item.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {item.code}
                                </td>
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {item.description}
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {item.unit}
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={
                                            item.active
                                                ? 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                                                : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                                        }
                                    >
                                        {item.active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Form
                                            action={`/items/${item.id}`}
                                            method="patch"
                                        >
                                            <input
                                                type="hidden"
                                                name="code"
                                                value={item.code}
                                            />
                                            <input
                                                type="hidden"
                                                name="description"
                                                value={item.description}
                                            />
                                            <input
                                                type="hidden"
                                                name="unit"
                                                value={item.unit}
                                            />
                                            <input
                                                type="hidden"
                                                name="active"
                                                value={item.active ? '0' : '1'}
                                            />
                                            <button
                                                type="submit"
                                                className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                            >
                                                {item.active
                                                    ? 'Deactivate'
                                                    : 'Activate'}
                                            </button>
                                        </Form>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setEditing(item);
                                                setConfirmingDelete(null);
                                            }}
                                            className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setConfirmingDelete(item);
                                                setEditing(null);
                                            }}
                                            className="rounded-md border border-border px-2 py-1 text-xs text-destructive hover:bg-destructive/10"
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
                        Delete {confirmingDelete.description}? This cannot be
                        undone.
                    </p>
                    <div className="mt-3 flex gap-2">
                        <Form
                            action={`/items/${confirmingDelete.id}`}
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
                        {editing ? 'Edit item' : 'Add item'}
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
                <ItemForm
                    key={editing?.id ?? 'create'}
                    mode={editing ? 'edit' : 'create'}
                    item={editing ?? undefined}
                    onCancel={() => setEditing(null)}
                />
            </div>

            <p className="mt-6 text-xs text-muted-foreground">
                Looking for suppliers?{' '}
                <Link
                    href="/suppliers"
                    className="text-primary hover:underline"
                >
                    Manage the supplier catalog
                </Link>
                .
            </p>
        </AppLayout>
    );
}
