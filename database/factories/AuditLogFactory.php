<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => null,
            'auditable_type' => 'App\\Models\\Profile',
            'auditable_id' => 1,
            'action' => 'create',
            'before' => null,
            'after' => null,
        ];
    }
}
