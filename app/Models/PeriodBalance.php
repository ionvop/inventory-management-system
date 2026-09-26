<?php

namespace App\Models;

use Database\Factories\PeriodBalanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A frozen snapshot of a supplier item's balance at period close (FR-6.2b).
 *
 * Closing a period records each supplier item's beginning and ending quantity
 * and cost here. The ending figures become the next period's beginning balance
 * (FR-6.2c), so a closed period's report always reproduces the same numbers
 * from this immutable snapshot (FR-7.4).
 *
 * @property int $id
 * @property int $period_id
 * @property int $supplier_item_id
 * @property string $beginning_quantity
 * @property string $beginning_cost
 * @property string $ending_quantity
 * @property string $ending_cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'period_id',
    'supplier_item_id',
    'beginning_quantity',
    'beginning_cost',
    'ending_quantity',
    'ending_cost',
])]
#[Hidden([])]
class PeriodBalance extends Model
{
    /** @use HasFactory<PeriodBalanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'beginning_quantity' => 'decimal:2',
            'beginning_cost' => 'decimal:2',
            'ending_quantity' => 'decimal:2',
            'ending_cost' => 'decimal:2',
        ];
    }

    /**
     * The period this snapshot belongs to.
     *
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    /**
     * The supplier item this snapshot describes.
     *
     * @return BelongsTo<SupplierItem, $this>
     */
    public function supplierItem(): BelongsTo
    {
        return $this->belongsTo(SupplierItem::class);
    }
}
