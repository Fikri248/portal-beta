<?php

namespace App\Models;

use App\Enums\AuditLogAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'bnnk_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit logs are immutable.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bnnk(): BelongsTo
    {
        return $this->belongsTo(Bnnk::class);
    }

    public function actionEnum(): ?AuditLogAction
    {
        return AuditLogAction::tryFrom($this->action);
    }

    public function actionLabel(): string
    {
        return $this->actionEnum()?->label() ?? $this->action;
    }
}
