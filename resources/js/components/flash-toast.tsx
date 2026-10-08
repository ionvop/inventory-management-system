import { usePage } from '@inertiajs/react';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

const AUTO_DISMISS_MS = 4000;

/**
 * Shows the shared `flash.success` / `flash.error` message as a toast.
 *
 * The message is set by a controller redirect (`->with('success', ...)`) and
 * shared through HandleInertiaRequests. It auto-dismisses so it never lingers
 * across unrelated navigations.
 */
export default function FlashToast() {
    const { flash } = usePage().props;
    const [dismissed, setDismissed] = useState(false);

    const message = flash?.success ?? flash?.error ?? null;
    const isError = !flash?.success && Boolean(flash?.error);

    // Re-arm the toast whenever a new message arrives.
    useEffect(() => {
        setDismissed(false);
    }, [message]);

    useEffect(() => {
        if (!message || dismissed) {
            return;
        }

        const timer = window.setTimeout(
            () => setDismissed(true),
            AUTO_DISMISS_MS,
        );

        return () => window.clearTimeout(timer);
    }, [message, dismissed]);

    if (!message || dismissed) {
        return null;
    }

    const Icon = isError ? XCircle : CheckCircle2;

    return (
        <div
            role="status"
            aria-live="polite"
            className={cn(
                'fixed right-4 bottom-4 z-50 flex max-w-sm items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg',
                isError
                    ? 'border-destructive/30 bg-destructive/10 text-destructive'
                    : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
            )}
        >
            <Icon className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <p className="flex-1">{message}</p>
            <button
                type="button"
                onClick={() => setDismissed(true)}
                className="rounded-md p-0.5 opacity-70 hover:opacity-100"
                aria-label="Dismiss notification"
            >
                <X className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}
