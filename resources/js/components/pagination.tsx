import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface PaginationProps {
    paginator: Paginated<unknown>;
    /**
     * Keep the scroll position when changing page. Useful for lists that sit
     * far down a long page, where jumping to the top would be disorienting.
     */
    preserveScroll?: boolean;
}

/**
 * Page links for a Laravel length-aware paginator.
 *
 * Renders the "Showing X–Y of Z" summary plus the previous/number/next links.
 * Nothing is rendered when everything fits on a single page.
 */
export default function Pagination({
    paginator,
    preserveScroll = false,
}: PaginationProps) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p className="text-xs text-muted-foreground">
                Showing {paginator.from ?? 0}–{paginator.to ?? 0} of{' '}
                {paginator.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {paginator.links.map((link, index) =>
                    link.url ? (
                        <button
                            key={index}
                            type="button"
                            onClick={() =>
                                router.get(
                                    link.url as string,
                                    {},
                                    { preserveState: true, preserveScroll },
                                )
                            }
                            className={cn(
                                'rounded-md px-3 py-1.5 text-xs',
                                link.active
                                    ? 'bg-primary font-medium text-primary-foreground'
                                    : 'border border-border text-muted-foreground hover:bg-muted',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground opacity-50"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
            </div>
        </div>
    );
}
