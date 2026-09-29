import { router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';

interface AuditChange {
    field: string;
    before: string | null;
    after: string | null;
}

interface AuditRow {
    id: number;
    created_at: string | null;
    profile_id: number | null;
    profile_name: string | null;
    action: string;
    record_type: string;
    record_type_label: string;
    auditable_id: number;
    label: string | null;
    changes: AuditChange[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface FilterOption {
    id: number;
    name: string;
    role: string;
}

interface RecordTypeOption {
    value: string;
    label: string;
}

interface Filters {
    profile_id: number | null;
    type: string | null;
    action: string | null;
    from: string | null;
    to: string | null;
}

interface AuditLogsProps {
    logs: Paginated<AuditRow>;
    filters: Filters;
    filterOptions: {
        profiles: FilterOption[];
        recordTypes: RecordTypeOption[];
        actions: string[];
    };
}

const actionClasses: Record<string, string> = {
    create: 'rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400',
    update: 'rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400',
    delete: 'rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive',
    reverse:
        'rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive',
    close: 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground',
    reopen: 'rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400',
};

const actionLabels: Record<string, string> = {
    create: 'Create',
    update: 'Update',
    delete: 'Delete',
    reverse: 'Reverse',
    close: 'Close',
    reopen: 'Reopen',
};

function actionLabel(action: string): string {
    return actionLabels[action] ?? action;
}

function actionClass(action: string): string {
    return (
        actionClasses[action] ??
        'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
    );
}

function formatValue(value: string | null): string {
    return value === null || value === '' ? '—' : value;
}

export default function AuditLogs({
    logs,
    filters,
    filterOptions,
}: AuditLogsProps) {
    const [form, setForm] = useState<Filters>(filters);

    const applyFilters = () => {
        const query: Record<string, string> = {};

        if (form.profile_id !== null) {
            query.profile_id = String(form.profile_id);
        }

        if (form.type) {
            query.type = form.type;
        }

        if (form.action) {
            query.action = form.action;
        }

        if (form.from) {
            query.from = form.from;
        }

        if (form.to) {
            query.to = form.to;
        }

        router.get('/audit-logs', query, { preserveState: true });
    };

    const clearFilters = () => {
        setForm({
            profile_id: null,
            type: null,
            action: null,
            from: null,
            to: null,
        });
        router.get('/audit-logs', {}, { preserveState: true });
    };

    return (
        <AppLayout title="Audit log">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">Audit log</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Every catalog change, transaction, batch, period close and
                    profile change is attributed to the acting profile. The
                    trail is read-only and cannot be edited or deleted.
                </p>
            </div>

            <div className="mb-6 grid gap-3 rounded-lg border border-border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5">
                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    Profile
                    <select
                        value={form.profile_id ?? ''}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                profile_id:
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                            })
                        }
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    >
                        <option value="">All profiles</option>
                        {filterOptions.profiles.map((profile) => (
                            <option key={profile.id} value={profile.id}>
                                {profile.name}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    Record type
                    <select
                        value={form.type ?? ''}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                type: event.target.value || null,
                            })
                        }
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    >
                        <option value="">All types</option>
                        {filterOptions.recordTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    Action
                    <select
                        value={form.action ?? ''}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                action: event.target.value || null,
                            })
                        }
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    >
                        <option value="">All actions</option>
                        {filterOptions.actions.map((action) => (
                            <option key={action} value={action}>
                                {actionLabel(action)}
                            </option>
                        ))}
                    </select>
                </label>

                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    From
                    <input
                        type="date"
                        value={form.from ?? ''}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                from: event.target.value || null,
                            })
                        }
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    />
                </label>

                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    To
                    <input
                        type="date"
                        value={form.to ?? ''}
                        onChange={(event) =>
                            setForm({
                                ...form,
                                to: event.target.value || null,
                            })
                        }
                        className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                    />
                </label>

                <div className="flex items-end gap-2 sm:col-span-2 lg:col-span-5">
                    <button
                        type="button"
                        onClick={applyFilters}
                        className="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Apply filters
                    </button>
                    <button
                        type="button"
                        onClick={clearFilters}
                        className="rounded-md border border-border px-3 py-2 text-sm text-muted-foreground hover:bg-muted"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border border-border bg-card">
                <table className="w-full text-left text-sm">
                    <thead className="border-b border-border bg-muted/50 text-xs text-muted-foreground uppercase">
                        <tr>
                            <th className="px-4 py-3 font-medium">When</th>
                            <th className="px-4 py-3 font-medium">Profile</th>
                            <th className="px-4 py-3 font-medium">Action</th>
                            <th className="px-4 py-3 font-medium">Record</th>
                            <th className="px-4 py-3 font-medium">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-sm text-muted-foreground"
                                >
                                    No audit entries match these filters.
                                </td>
                            </tr>
                        )}
                        {logs.data.map((log) => (
                            <tr
                                key={log.id}
                                className="border-b border-border align-top last:border-0"
                            >
                                <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                    {log.created_at ?? '—'}
                                </td>
                                <td className="px-4 py-3 text-foreground">
                                    {log.profile_name ?? '—'}
                                </td>
                                <td className="px-4 py-3">
                                    <span className={actionClass(log.action)}>
                                        {actionLabel(log.action)}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    <span className="font-medium text-foreground">
                                        {log.record_type_label}
                                    </span>{' '}
                                    #{log.auditable_id}
                                    {log.label ? (
                                        <span className="block text-xs">
                                            {log.label}
                                        </span>
                                    ) : null}
                                </td>
                                <td className="px-4 py-3">
                                    {log.changes.length === 0 ? (
                                        <span className="text-xs text-muted-foreground">
                                            —
                                        </span>
                                    ) : (
                                        <ul className="space-y-0.5 text-xs">
                                            {log.changes.map((change) => (
                                                <li
                                                    key={change.field}
                                                    className="text-muted-foreground"
                                                >
                                                    <span className="font-medium text-foreground">
                                                        {change.field}
                                                    </span>
                                                    {': '}
                                                    {formatValue(change.before)}
                                                    {' → '}
                                                    {formatValue(change.after)}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {logs.last_page > 1 && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p className="text-xs text-muted-foreground">
                        Showing {logs.from ?? 0}–{logs.to ?? 0} of {logs.total}
                    </p>
                    <div className="flex flex-wrap gap-1">
                        {logs.links.map((link, index) =>
                            link.url ? (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() =>
                                        router.get(
                                            link.url as string,
                                            {},
                                            { preserveState: true },
                                        )
                                    }
                                    className={
                                        link.active
                                            ? 'rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground'
                                            : 'rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted'
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground opacity-50"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
