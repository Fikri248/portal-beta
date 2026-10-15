<?php

namespace Database\Factories;

use App\Models\Bnnk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bnnk>
 */
class BnnkFactory extends Factory
{
    protected $model = Bnnk::class;

    public function definition(): array
    {
        return [
            'name' => 'BNNK ' . fake()->city(),
        ];
    }
}
