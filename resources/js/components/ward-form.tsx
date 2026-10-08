import { Form } from '@inertiajs/react';

export interface Ward {
    id: number;
    name: string;
    active: boolean;
    has_transactions: boolean;
}

interface WardFormProps {
    mode: 'create' | 'edit';
    ward?: Ward;
    onCancel?: () => void;
    onSuccess?: () => void;
}

export default function WardForm({
    mode,
    ward,
    onCancel,
    onSuccess,
}: WardFormProps) {
    const isEdit = mode === 'edit';

    return (
        <Form
            action={isEdit ? `/wards/${ward?.id}` : '/wards'}
            method={isEdit ? 'patch' : 'post'}
            resetOnSuccess
            onSuccess={onSuccess}
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
                            defaultValue={ward?.name}
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
                            Status
                        </label>
                        <select
                            name="active"
                            defaultValue={
                                ward ? (ward.active ? '1' : '0') : '1'
                            }
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        >
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        {errors.active && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.active}
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
                                  : 'Add ward'}
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
