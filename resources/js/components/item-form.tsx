import { Form } from '@inertiajs/react';

export interface Item {
    id: number;
    code: string;
    description: string;
    unit: string;
    active: boolean;
}

interface ItemFormProps {
    mode: 'create' | 'edit';
    item?: Item;
    onCancel?: () => void;
}

export default function ItemForm({ mode, item, onCancel }: ItemFormProps) {
    const isEdit = mode === 'edit';

    return (
        <Form
            action={isEdit ? `/items/${item?.id}` : '/items'}
            method={isEdit ? 'patch' : 'post'}
        >
            {({ errors, processing }) => (
                <div className="grid gap-3 sm:grid-cols-4">
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Code
                        </label>
                        <input
                            type="text"
                            name="code"
                            defaultValue={item?.code}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.code && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.code}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Description
                        </label>
                        <input
                            type="text"
                            name="description"
                            defaultValue={item?.description}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Unit
                        </label>
                        <input
                            type="text"
                            name="unit"
                            placeholder="e.g. sachet"
                            defaultValue={item?.unit}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.unit && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.unit}
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
                                  : 'Add item'}
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
