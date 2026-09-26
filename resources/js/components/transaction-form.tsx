import { Form } from '@inertiajs/react';
import { useState } from 'react';

export interface SupplierItemOption {
    id: number;
    supplier_name: string | null;
    item_code: string | null;
    item_description: string | null;
    unit: string | null;
    price: string;
    balance: {
        quantity: number;
        total_cost: number;
    };
}

export interface WardOption {
    id: number;
    name: string;
}

export interface TransactionTypeOption {
    value: string;
    label: string;
}

interface TransactionFormProps {
    supplierItems: SupplierItemOption[];
    wards: WardOption[];
    types: TransactionTypeOption[];
    canOverride: boolean;
}

export default function TransactionForm({
    supplierItems,
    wards,
    types,
    canOverride,
}: TransactionFormProps) {
    const [type, setType] = useState('received');
    const [supplierItemId, setSupplierItemId] = useState('');

    const selected = supplierItems.find(
        (supplierItem) => String(supplierItem.id) === supplierItemId,
    );

    const requiresBatch = type === 'received';
    const requiresWard = type === 'return_from_ward';
    const wardOptional = type === 'consumption';
    const requiresRemark = type === 'write_off';

    return (
        <Form action="/transactions" method="post" resetOnSuccess>
            {({ errors, processing }) => (
                <div className="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Movement type
                        </label>
                        <select
                            name="type"
                            value={type}
                            onChange={(event) => setType(event.target.value)}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        >
                            {types.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        {errors.type && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.type}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Supplier item
                        </label>
                        <select
                            name="supplier_item_id"
                            value={supplierItemId}
                            onChange={(event) =>
                                setSupplierItemId(event.target.value)
                            }
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        >
                            <option value="" disabled>
                                Select a supplier item
                            </option>
                            {supplierItems.map((supplierItem) => (
                                <option
                                    key={supplierItem.id}
                                    value={supplierItem.id}
                                >
                                    {supplierItem.supplier_name ?? '—'} —{' '}
                                    {supplierItem.item_code ?? '—'}
                                </option>
                            ))}
                        </select>
                        {errors.supplier_item_id && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.supplier_item_id}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Quantity
                        </label>
                        <input
                            type="number"
                            name="quantity"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.quantity && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.quantity}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Transaction date
                        </label>
                        <input
                            type="date"
                            name="transaction_date"
                            defaultValue={new Date().toISOString().slice(0, 10)}
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.transaction_date && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.transaction_date}
                            </p>
                        )}
                    </div>

                    {(requiresWard || wardOptional) && (
                        <div>
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Ward{wardOptional ? ' (optional)' : ''}
                            </label>
                            <select
                                name="ward_id"
                                defaultValue=""
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                            >
                                <option value="">
                                    {requiresWard ? 'Select a ward' : 'No ward'}
                                </option>
                                {wards.map((ward) => (
                                    <option key={ward.id} value={ward.id}>
                                        {ward.name}
                                    </option>
                                ))}
                            </select>
                            {errors.ward_id && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.ward_id}
                                </p>
                            )}
                        </div>
                    )}

                    {requiresBatch && (
                        <>
                            <div>
                                <label className="mb-1 block text-xs text-muted-foreground">
                                    Batch number
                                </label>
                                <input
                                    type="text"
                                    name="batch_number"
                                    className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                                />
                                {errors.batch_number && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.batch_number}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-1 block text-xs text-muted-foreground">
                                    Expiration date
                                </label>
                                <input
                                    type="date"
                                    name="expiration_date"
                                    className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                                />
                                {errors.expiration_date && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.expiration_date}
                                    </p>
                                )}
                            </div>
                        </>
                    )}

                    <div className="sm:col-span-3">
                        <label className="mb-1 block text-xs text-muted-foreground">
                            Remark{requiresRemark ? '' : ' (optional)'}
                        </label>
                        <input
                            type="text"
                            name="remark"
                            placeholder={
                                requiresRemark
                                    ? 'Reason for write-off (expired/damaged)'
                                    : ''
                            }
                            className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                        />
                        {errors.remark && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.remark}
                            </p>
                        )}
                    </div>

                    {canOverride && (
                        <div className="sm:col-span-3">
                            <label className="mb-1 block text-xs text-muted-foreground">
                                Override reason (administrators only)
                            </label>
                            <input
                                type="text"
                                name="override_reason"
                                placeholder="Required only when allowing a negative balance"
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm"
                            />
                            {errors.override_reason && (
                                <p className="mt-1 text-xs text-destructive">
                                    {errors.override_reason}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="flex items-end gap-3 sm:col-span-3">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing ? 'Recording...' : 'Record movement'}
                        </button>
                        {selected && (
                            <p className="text-xs text-muted-foreground">
                                Unit cost {selected.price} · current balance{' '}
                                {selected.balance.quantity}
                            </p>
                        )}
                    </div>
                </div>
            )}
        </Form>
    );
}
