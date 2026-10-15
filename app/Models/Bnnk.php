<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bnnk extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function tatRegisters(): HasMany
    {
        return $this->hasMany(TatRegister::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}

