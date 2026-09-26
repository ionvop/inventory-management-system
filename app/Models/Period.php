<?php

namespace App\Models;

use Database\Factories\PeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $year
 * @property int $month
 * @property string $status
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property string|null $reopened_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['year', 'month', 'status', 'closed_at', 'closed_by', 'reopened_reason'])]
#[Hidden([])]
class Period extends Model
{
    /** @use HasFactory<PeriodFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    /**
     * The transactions recorded within this period.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The frozen per-supplier-item balance snapshots taken at close (FR-6.2b).
     *
     * @return HasMany<PeriodBalance, $this>
     */
    public function balances(): HasMany
    {
        return $this->hasMany(PeriodBalance::class);
    }

    /**
     * The period immediately preceding this one, if it exists.
     *
     * Periods are ordered by year then month, so the previous period is the
     * greatest (year, month) strictly before this one. Used to carry a closed
     * period's ending balance forward as the next period's beginning (FR-6.2c).
     */
    public function previous(): ?self
    {
        return static::query()
            ->where(function ($query) {
                $query->where('year', '<', $this->year)
                    ->orWhere(function ($query) {
                        $query->where('year', $this->year)
                            ->where('month', '<', $this->month);
                    });
            })
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();
    }

    /**
     * Resolve the period a given date falls into, creating it when absent.
     *
     * Transactions are organised into monthly periods (FR-6.1). Until period
     * management is built, the period for a transaction is derived from its
     * date, so recording a transaction in a new month opens that month's
     * period automatically.
     */
    public static function forDate(Carbon $date): self
    {
        return static::query()->firstOrCreate(
            [
                'year' => (int) $date->format('Y'),
                'month' => (int) $date->format('n'),
            ],
            ['status' => 'open'],
        );
    }
}
