import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';
import WardForm, { type Ward } from '@/components/ward-form';
import AppLayout from '@/layouts/app-layout';

interface WardsProps {
    wards: Ward[];
}

export default function Wards({ wards }: WardsProps) {
    const [editing, setEditing] = useState<Ward | null>(null);
    const [confirmingDelete, setConfirmingDelete] = useState<Ward | null>(null);

    return (
        <AppLayout title="Wards">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-bold text-foreground">Wards</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Wards that consumption and ward returns are attributed
                        to.
                    </p>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {wards.length === 0 && (
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No wards yet. Add the first one below.
                                </td>
                            </tr>
                        )}
                        {wards.map((ward) => (
                            <tr
                                key={ward.id}
                                className="border-b border-border last:border-0"
                            >
                                <td className="px-4 py-3 font-medium text-foreground">
                                    {ward.name}
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={
                                            ward.active
                                                ? 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                                                : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                                        }
                                    >
                                        {ward.active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-end gap-2">
                                        <Form
                                            action={`/wards/${ward.id}`}
                                            method="patch"
                                        >
                                            <input
                                                type="hidden"
                                                name="name"
                                                value={ward.name}
                                            />
                                            <input
                                                type="hidden"
                                                name="active"
                                                value={ward.active ? '0' : '1'}
                                            />
                                            <button
                                                type="submit"
                                                className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                            >
                                                {ward.active
                                                    ? 'Deactivate'
                                                    : 'Activate'}
                                            </button>
                                        </Form>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setEditing(ward);
                                                setConfirmingDelete(null);
                                            }}
                                            className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            disabled={ward.has_transactions}
                                            title={
                                                ward.has_transactions
                                                    ? 'This ward has transactions and cannot be deleted. Deactivate it instead.'
                                                    : undefined
                                            }
                                            onClick={() => {
                                                setConfirmingDelete(ward);
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
                        Delete {confirmingDelete.name}? This cannot be undone.
                    </p>
                    <div className="mt-3 flex gap-2">
                        <Form
                            action={`/wards/${confirmingDelete.id}`}
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
                        {editing ? 'Edit ward' : 'Add ward'}
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
                <WardForm
                    key={editing?.id ?? 'create'}
                    mode={editing ? 'edit' : 'create'}
                    ward={editing ?? undefined}
                    onCancel={() => setEditing(null)}
                    onSuccess={() => setEditing(null)}
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
