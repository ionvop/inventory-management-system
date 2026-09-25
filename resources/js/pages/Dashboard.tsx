import { Head, Link } from '@inertiajs/react';
import AppearanceToggle from '@/components/appearance-toggle';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex min-h-screen flex-col bg-background p-6 text-foreground">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-xl font-bold text-foreground">
                        Dashboard
                    </h1>
                    <div className="flex items-center gap-3">
                        <AppearanceToggle />
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground hover:bg-muted"
                        >
                            Switch profile
                        </Link>
                    </div>
                </div>
                <p className="mt-4 text-sm text-muted-foreground">
                    Welcome. The workspace is ready for the next increment.
                </p>
            </div>
        </>
    );
}
