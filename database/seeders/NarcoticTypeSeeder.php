<?php
namespace Database\Seeders;
use App\Models\NarcoticType;
use Illuminate\Database\Seeder;
class NarcoticTypeSeeder extends Seeder { public function run(): void { foreach(['Tablet MDMA (Ekstasi)','Kristal Metamfetamina (Shabu)','Dimetiltriptamina (DMT)','Carisoprodol','Tenamfetamina (MDA)'] as $name) NarcoticType::firstOrCreate(['name'=>$name],['is_active'=>true]); } }
