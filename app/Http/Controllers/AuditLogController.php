<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Item;
use App\Models\Period;
use App\Models\Profile;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays the audit trail (FR-8.2).
 *
 * Every create/edit/delete on catalog data, transactions, batches, periods and
 * profiles is written by AuditLogger (FR-8.1). This screen makes that trail
 * reviewable by administrators, filterable by profile, date range, record type
 * and action. It is strictly read-only: audit rows are never edited or deleted,
 * so the trail remains a faithful record of what happened.
 */
class AuditLogController extends Controller
{
    /**
     * The number of audit rows shown per page.
     */
    protected const int PER_PAGE = 50;

    /**
     * The auditable record types, keyed by their short (URL-friendly) name.
     *
     * The stored `auditable_type` is the model's morph class, so the short name
     * is mapped back to the class when filtering rather than matching on a
     * fragile string suffix.
     *
     * @var array<string, class-string>
     */
    protected const array RECORD_TYPES = [
        'Profile' => Profile::class,
        'Supplier' => Supplier::class,
        'Item' => Item::class,
        'SupplierItem' => SupplierItem::class,
        'Batch' => Batch::class,
        'Transaction' => Transaction::class,
        'Period' => Period::class,
    ];

    /**
     * Human-readable labels for the auditable record types.
     *
     * @var array<string, string>
     */
    protected const array RECORD_TYPE_LABELS = [
        'Profile' => 'Profile',
        'Supplier' => 'Supplier',
        'Item' => 'Item',
        'SupplierItem' => 'Supplier item',
        'Batch' => 'Batch',
        'Transaction' => 'Transaction',
        'Period' => 'Period',
    ];

    /**
     * Display the audit trail, newest first, with optional filters.
     */
    public function index(): Response
    {
        $filters = $this->filters();

        $logs = $this->query($filters)
            // A profile may have been soft-deleted since the action was
            // recorded, but its name must still resolve on the trail (FR-1.5).
            ->with(['profile' => fn ($query) => $query->withTrashed()])
            ->orderByDesc('id')
            ->paginate(static::PER_PAGE)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'created_at' => $log->created_at?->toDateTimeString(),
                'profile_id' => $log->profile_id,
                'profile_name' => $log->profile?->name,
                'action' => $log->action,
                'record_type' => $this->recordType($log),
                'record_type_label' => $this->recordTypeLabel($log),
                'auditable_id' => $log->auditable_id,
                'label' => $this->label($log),
                'changes' => $this->changes($log),
            ]);

        return Inertia::render('AuditLogs', [
            'logs' => $logs,
            'filters' => $filters,
            'filterOptions' => [
                // Deleted profiles still appear on historical rows, so the
                // filter must offer them too (FR-1.5).
                'profiles' => Profile::withTrashed()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Profile $profile): array => [
                        'id' => $profile->id,
                        'name' => $profile->name,
                        'role' => $profile->role,
                    ]),
                'recordTypes' => collect(static::RECORD_TYPES)
                    ->map(fn (string $class, string $short): array => [
                        'value' => $short,
                        'label' => static::RECORD_TYPE_LABELS[$short] ?? $short,
                    ])
                    ->values(),
                'actions' => AuditLog::query()
                    ->distinct()
                    ->orderBy('action')
                    ->pluck('action')
                    ->map(fn (mixed $action): string => (string) $action)
                    ->values(),
            ],
        ]);
    }

    /**
     * The active filters, normalised from the query string.
     *
     * @return array{profile_id: int|null, type: string|null, action: string|null, from: string|null, to: string|null}
     */
    protected function filters(): array
    {
        return [
            'profile_id' => $this->intOrNull(Request::query('profile_id')),
            'type' => $this->stringOrNull(Request::query('type')),
            'action' => $this->stringOrNull(Request::query('action')),
            'from' => $this->dateOrNull(Request::query('from')),
            'to' => $this->dateOrNull(Request::query('to')),
        ];
    }

    /**
     * Build the filtered audit log query.
     *
     * @param  array{profile_id: int|null, type: string|null, action: string|null, from: string|null, to: string|null}  $filters
     * @return Builder<AuditLog>
     */
    protected function query(array $filters): Builder
    {
        $type = $filters['type'];

        return AuditLog::query()
            ->when(
                $filters['profile_id'] !== null,
                fn (Builder $query) => $query->where('profile_id', $filters['profile_id']),
            )
            ->when(
                $type !== null && isset(static::RECORD_TYPES[$type]),
                fn (Builder $query) => $query->where('auditable_type', static::RECORD_TYPES[$type]),
            )
            ->when(
                $filters['action'] !== null,
                fn (Builder $query) => $query->where('action', $filters['action']),
            )
            ->when(
                $filters['from'] !== null,
                fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']),
            )
            ->when(
                $filters['to'] !== null,
                fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']),
            );
    }

    /**
     * The short name of the audited record type.
     */
    protected function recordType(AuditLog $log): string
    {
        return class_basename($log->auditable_type);
    }

    /**
     * The human-readable label for the audited record type.
     */
    protected function recordTypeLabel(AuditLog $log): string
    {
        $type = $this->recordType($log);

        return static::RECORD_TYPE_LABELS[$type] ?? $type;
    }

    /**
     * A short human identifier for the audited record, taken from its snapshot.
     *
     * Falls back to null when the snapshot carries no obvious label, in which
     * case the screen shows the record type and id only.
     */
    protected function label(AuditLog $log): ?string
    {
        $snapshot = $log->after ?? $log->before ?? [];

        foreach (['name', 'code', 'batch_number', 'type'] as $key) {
            $value = $snapshot[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * The changed fields between the before and after snapshots.
     *
     * Only fields whose value actually differs are returned, so a row stays
     * scannable instead of dumping the full snapshot.
     *
     * @return list<array{field: string, before: string|null, after: string|null}>
     */
    protected function changes(AuditLog $log): array
    {
        $before = $log->before ?? [];
        $after = $log->after ?? [];

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));

        $changes = [];

        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old === $new) {
                continue;
            }

            $changes[] = [
                'field' => (string) $key,
                'before' => $this->stringify($old),
                'after' => $this->stringify($new),
            ];
        }

        return $changes;
    }

    /**
     * Render a snapshot value as a display string.
     */
    protected function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value) ?: null;
    }

    /**
     * Normalise a query value to an integer, or null when absent.
     */
    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Normalise a query value to a non-empty string, or null when absent.
     */
    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Normalise a query value to a plain date string, or null when invalid.
     */
    protected function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
