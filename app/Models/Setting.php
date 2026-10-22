<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A stored override for one of the tunable inventory thresholds.
 *
 * Settings are a key/value store for values the department can tune at runtime
 * without a code change (e.g. the near-expiry window). Only values that differ
 * from the configured default are stored; a missing row means "use the default
 * from config/inventory.php".
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
#[Hidden([])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;
}
