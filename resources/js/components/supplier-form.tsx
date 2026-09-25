import { Form } from '@inertiajs/react';

export interface Supplier {
    id: number;
    name: string;
    contract_status: string | null;
    active: boolean;
}

interface SupplierFormProps {
    mode: 'create' | 'edit';
    supplier?: Supplier;
    onCancel?: () => void;
}

export default function SupplierForm({
    mode,
    supplier,
    onCancel,
}: SupplierFormProps) {
    const isEdit = mode === 'edit';

    return (
        <Form
            action={isEdit ? `/suppliers/${supplier?.id}` : '/suppliers'}
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
                            defaultValue={supplier?.name}
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
                            Contract status
                        </label>
                        <input
                            type="text"
                            name="contract_status"
                            placeholder="e.g. New contract"
                            defaultValue={supplier?.contract_status ?? ''}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.contract_status && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.contract_status}
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
                                  : 'Add supplier'}
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
