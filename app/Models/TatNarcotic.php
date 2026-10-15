<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TatNarcotic extends Model { use HasFactory; protected $guarded=[]; protected function casts(): array { return ['jumlah'=>'decimal:3']; } public function tatRegister(): BelongsTo { return $this->belongsTo(TatRegister::class); } public function narcoticType(): BelongsTo { return $this->belongsTo(NarcoticType::class); } public function unit(): BelongsTo { return $this->belongsTo(Unit::class); } }
