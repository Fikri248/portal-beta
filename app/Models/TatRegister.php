<?php
namespace App\Models;
use App\Enums\{Gender, RecommendationExecution, TatRecommendation, TatRegisterStatus};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
/**
 * @property string|null $pasal_yang_disangkakan
 */
class TatRegister extends Model {
 use HasFactory, SoftDeletes;
 protected $guarded = ['id','bnnk_id','status','deleted_at'];
 protected function casts(): array { return ['status'=>TatRegisterStatus::class,'rekomendasi_tat'=>TatRecommendation::class,'pelaksanaan_rekomendasi'=>RecommendationExecution::class,'jenis_kelamin'=>Gender::class,'tanggal_surat'=>'date','tanggal_berkas_diterima'=>'date','tanggal_pelaksanaan'=>'date','tanggal_penangkapan'=>'date','tanggal_lahir'=>'date','usia'=>'integer']; }
 public function bnnk(): BelongsTo { return $this->belongsTo(Bnnk::class); }
 public function narcotics(): HasMany { return $this->hasMany(TatNarcotic::class); }
}
