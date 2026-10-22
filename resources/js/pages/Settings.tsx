import { router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

interface SettingRow {
    key: string;
    label: string;
    type: 'int' | 'float';
    min: number;
    unit: string;
    help: string;
    default: number;
    value: number;
    is_overridden: boolean;
}

interface SettingsProps {
    settings: SettingRow[];
}

export default function Settings({ settings }: SettingsProps) {
    const { data, setData, post, processing, errors } = useForm(
        Object.fromEntries(
            settings.map((setting) => [setting.key, String(setting.value)]),
        ),
    );

    const overriddenCount = settings.filter((row) => row.is_overridden).length;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post('/settings');
    };

    const resetField = (key: string) => {
        router.post('/settings/reset', { key }, { preserveScroll: true });
    };

    const resetAll = () => {
        router.post('/settings/reset', {}, { preserveScroll: true });
    };

    return (
        <AppLayout title="Settings">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">
                    Inventory settings
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Tune the thresholds the system uses across the dashboard,
                    batches and bids. Values you override take effect
                    immediately; reset a value to fall back to its configured
                    default.
                </p>
                {overriddenCount > 0 && (
                    <p className="mt-2 text-sm font-medium text-amber-600 dark:text-amber-400">
                        {overriddenCount} threshold
                        {overriddenCount === 1 ? '' : 's'} overridden from the
                        default.
                    </p>
                )}
            </div>

            <form onSubmit={submit}>
                <div className="space-y-4">
                    {settings.map((setting) => (
                        <div
                            key={setting.key}
                            className="rounded-lg border border-border bg-card p-4"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <h2 className="text-sm font-semibold text-foreground">
                                            {setting.label}
                                        </h2>
                                        {setting.is_overridden && (
                                            <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400">
                                                Overridden
                                            </span>
                                        )}
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {setting.help}
                                    </p>
                                </div>

                                <div className="w-full sm:w-56">
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="number"
                                            value={data[setting.key] ?? ''}
                                            onChange={(event) =>
                                                setData(
                                                    setting.key,
                                                    event.target.value,
                                                )
                                            }
                                            min={setting.min}
                                            step={
                                                setting.type === 'int'
                                                    ? '1'
                                                    : '0.01'
                                            }
                                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                                        />
                                        <span className="shrink-0 text-xs text-muted-foreground">
                                            {setting.unit}
                                        </span>
                                    </div>
                                    {errors[setting.key] && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors[setting.key]}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-3 flex items-center justify-between border-t border-border pt-3 text-xs text-muted-foreground">
                                <span>
                                    Default:{' '}
                                    <span className="text-foreground">
                                        {setting.default.toLocaleString()}{' '}
                                        {setting.unit}
                                    </span>
                                </span>
                                <button
                                    type="button"
                                    onClick={() => resetField(setting.key)}
                                    disabled={!setting.is_overridden}
                                    className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-6 flex items-center gap-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Save settings'}
                    </button>
                </div>
            </form>

            <div className="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border bg-card p-4">
                <div>
                    <h2 className="text-sm font-semibold text-foreground">
                        Reset to defaults
                    </h2>
                    <p className="mt-1 text-xs text-muted-foreground">
                        Clear every override so each threshold uses its
                        configured default again.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={resetAll}
                    disabled={overriddenCount === 0}
                    className="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                >
                    Reset all
                </button>
            </div>
        </AppLayout>
    );
}
