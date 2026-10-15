<?php
namespace Database\Factories;
use App\Models\NarcoticType;
use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<NarcoticType> */
class NarcoticTypeFactory extends Factory { protected $model=NarcoticType::class; public function definition(): array { return ['name'=>fake()->unique()->words(2,true),'is_active'=>true]; } }
