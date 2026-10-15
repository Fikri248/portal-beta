<?php
namespace Database\Seeders;
use App\Models\Unit;
use Illuminate\Database\Seeder;
class UnitSeeder extends Seeder { public function run(): void { foreach(['BATANG','BOTOL','BUTIR','GRAM','ONS'] as $name) Unit::firstOrCreate(['name'=>$name],['is_active'=>true]); } }
