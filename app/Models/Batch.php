<?php

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_item_id
 * @property string $batch_number
 * @property Carbon $expiration_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_item_id', 'batch_number', 'expiration_date', 'status'])]
#[Hidden([])]
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    /**
     * The supplier item this batch belongs to.
     *
     * @return BelongsTo<SupplierItem, $this>
     */
    public function supplierItem(): BelongsTo
    {
        return $this->belongsTo(SupplierItem::class);
    }
}
