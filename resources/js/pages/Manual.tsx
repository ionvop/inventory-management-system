import { router } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';

interface ManualSection {
    slug: string;
    title: string;
}

interface ManualProps {
    sections: ManualSection[];
    selectedSlug: string | null;
    content: string;
}

export default function Manual({
    sections,
    selectedSlug,
    content,
}: ManualProps) {
    const selected = sections.find((section) => section.slug === selectedSlug);

    const openSection = (slug: string) => {
        router.get(
            '/manual',
            { section: slug },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AppLayout title="User Manual">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-foreground">
                    User Manual
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    A complete, end-to-end guide to using the inventory system.
                    Pick a topic from the list to read it.
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
                {/* Section navigation */}
                <nav
                    aria-label="Manual sections"
                    className="lg:sticky lg:top-20 lg:self-start"
                >
                    <div className="overflow-hidden rounded-lg border border-border bg-card">
                        <div className="flex items-center gap-2 border-b border-border px-4 py-3">
                            <BookOpen
                                className="h-4 w-4 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span className="text-xs font-medium text-muted-foreground uppercase">
                                Contents
                            </span>
                        </div>
                        <ul className="max-h-[70vh] overflow-y-auto p-2">
                            {sections.map((section) => {
                                const isActive = section.slug === selectedSlug;

                                return (
                                    <li key={section.slug}>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                openSection(section.slug)
                                            }
                                            aria-current={
                                                isActive ? 'page' : undefined
                                            }
                                            className={cn(
                                                'w-full rounded-md px-3 py-2 text-left text-sm transition-colors',
                                                isActive
                                                    ? 'bg-muted font-medium text-foreground'
                                                    : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground',
                                            )}
                                        >
                                            {section.title}
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                </nav>

                {/* Rendered Markdown */}
                <article className="min-w-0 overflow-hidden rounded-lg border border-border bg-card p-6 sm:p-8">
                    {selected && (
                        <p className="mb-4 text-xs font-medium text-muted-foreground uppercase">
                            {selected.title}
                        </p>
                    )}
                    {content ? (
                        <div
                            className="manual-content"
                            // The HTML is generated server-side from trusted
                            // Markdown files with raw HTML stripped.
                            dangerouslySetInnerHTML={{ __html: content }}
                        />
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No manual content is available yet.
                        </p>
                    )}
                </article>
            </div>
        </AppLayout>
    );
}
