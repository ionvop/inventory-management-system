import { Form } from '@inertiajs/react';
import { roleLabels, roleOptions, type ProfileRole } from '@/lib/profile-roles';

interface ProfileFormProps {
    mode: 'create' | 'edit';
    profile?: {
        id: number;
        name: string;
        role: ProfileRole;
    };
    onCancel?: () => void;
}

export default function ProfileForm({
    mode,
    profile,
    onCancel,
}: ProfileFormProps) {
    const isEdit = mode === 'edit';

    return (
        <Form
            action={isEdit ? `/profiles/${profile?.id}` : '/profiles'}
            method={isEdit ? 'patch' : 'post'}
        >
            {({ errors, processing }) => (
                <div className="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            defaultValue={profile?.name}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.name && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Role
                        </label>
                        <select
                            name="role"
                            defaultValue={profile?.role ?? 'staff'}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        >
                            {roleOptions.map((role) => (
                                <option key={role} value={role}>
                                    {roleLabels[role]}
                                </option>
                            ))}
                        </select>
                        {errors.role && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.role}
                            </p>
                        )}
                    </div>
                    <div className="flex items-end gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing
                                ? isEdit
                                    ? 'Saving...'
                                    : 'Adding...'
                                : isEdit
                                  ? 'Save changes'
                                  : 'Add profile'}
                        </button>
                        {isEdit && onCancel && (
                            <button
                                type="button"
                                onClick={onCancel}
                                className="rounded-md border border-border px-4 py-2 text-sm text-muted-foreground hover:bg-muted"
                            >
                                Cancel
                            </button>
                        )}
                    </div>
                </div>
            )}
        </Form>
    );
}
