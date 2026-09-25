import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppearanceToggle from '@/components/appearance-toggle';
import ProfileForm from '@/components/profile-form';
import { roleLabels, type ProfileRole } from '@/lib/profile-roles';

interface Profile {
    id: number;
    name: string;
    role: ProfileRole;
}

interface ProfilePickerProps {
    profiles: Profile[];
}

export default function ProfilePicker({ profiles }: ProfilePickerProps) {
    // Start in managing mode when there are no profiles yet, so the very first
    // profile can be created (FR-1.4).
    const [managing, setManaging] = useState(profiles.length === 0);
    const [editing, setEditing] = useState<Profile | null>(null);
    const [confirmingDelete, setConfirmingDelete] = useState<Profile | null>(
        null,
    );

    return (
        <>
            <Head title="Select Profile" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-indigo-50 to-background p-6 text-foreground dark:from-gray-950 dark:to-background">
                <div className="w-full max-w-2xl">
                    <div className="mb-8 flex flex-col items-center gap-4 text-center">
                        <div>
                            <h1 className="text-2xl font-bold text-foreground">
                                Inventory Manager
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Select a profile to continue
                            </p>
                        </div>
                        <AppearanceToggle />
                    </div>

                    <div className="mb-6 flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            {managing
                                ? 'Managing profiles'
                                : 'Choose who is using the system'}
                        </p>
                        <button
                            type="button"
                            onClick={() => {
                                setManaging((value) => !value);
                                setEditing(null);
                                setConfirmingDelete(null);
                            }}
                            className="rounded-md border border-border bg-card px-3 py-1.5 text-sm font-medium text-foreground hover:bg-muted"
                        >
                            {managing ? 'Done managing' : 'Manage profiles'}
                        </button>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {profiles.map((profile) => (
                            <div
                                key={profile.id}
                                className="flex flex-col rounded-lg border border-border bg-card p-4 shadow-sm"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {initials(profile.name)}
                                    </div>
                                    {managing && (
                                        <div className="flex gap-1">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setEditing(profile);
                                                    setConfirmingDelete(null);
                                                }}
                                                className="rounded-md border border-border px-2 py-1 text-xs text-muted-foreground hover:bg-muted"
                                                title="Edit profile"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setConfirmingDelete(
                                                        profile,
                                                    );
                                                    setEditing(null);
                                                }}
                                                className="rounded-md border border-border px-2 py-1 text-xs text-destructive hover:bg-destructive/10"
                                                title="Delete profile"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <div className="mt-3">
                                    <p className="font-medium text-foreground">
                                        {profile.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {roleLabels[profile.role]}
                                    </p>
                                </div>
                                {managing &&
                                confirmingDelete?.id === profile.id ? (
                                    <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/10 p-3">
                                        <p className="text-xs text-destructive">
                                            Delete {profile.name}? This cannot
                                            be undone.
                                        </p>
                                        <div className="mt-2 flex gap-2">
                                            <Form
                                                action={`/profiles/${profile.id}`}
                                                method="delete"
                                                className="flex-1"
                                            >
                                                {({ processing }) => (
                                                    <button
                                                        type="submit"
                                                        disabled={processing}
                                                        className="w-full rounded-md bg-destructive px-3 py-1.5 text-xs font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50"
                                                    >
                                                        {processing
                                                            ? 'Deleting...'
                                                            : 'Delete'}
                                                    </button>
                                                )}
                                            </Form>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setConfirmingDelete(null)
                                                }
                                                className="rounded-md border border-border px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <Link
                                        href={`/profiles/select/${profile.id}`}
                                        method="post"
                                        as="button"
                                        className="mt-3 w-full rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                    >
                                        Select
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>

                    {managing && (
                        <div className="mt-8 rounded-lg border border-border bg-card p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="text-sm font-semibold text-foreground">
                                    {editing ? 'Edit profile' : 'Add profile'}
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
                            <ProfileForm
                                key={editing?.id ?? 'create'}
                                mode={editing ? 'edit' : 'create'}
                                profile={editing ?? undefined}
                                onCancel={() => setEditing(null)}
                            />
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

function initials(name: string): string {
    const parts = name.trim().split(/\s+/);
    const first = parts[0]?.[0] ?? '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return (first + last).toUpperCase();
}
