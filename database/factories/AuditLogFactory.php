<?php

namespace Database\Factories;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'action' => AuditLogAction::UserUpdated->value,
            'description' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];
    }
}
