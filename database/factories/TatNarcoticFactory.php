<?php
namespace Database\Factories;
use App\Models\{NarcoticType,TatNarcotic,TatRegister,Unit};
use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<TatNarcotic> */
class TatNarcoticFactory extends Factory { protected $model=TatNarcotic::class; public function definition(): array { return ['tat_register_id'=>TatRegister::factory(),'narcotic_type_id'=>NarcoticType::factory(),'jumlah'=>fake()->randomFloat(3,0,100),'unit_id'=>Unit::factory()]; } }
