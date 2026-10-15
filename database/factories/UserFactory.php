<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Bnnk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Bnnp,
            'bnnk_id' => null,
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function bnnp(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Bnnp,
            'bnnk_id' => null,
        ]);
    }

    public function bnnk(?Bnnk $bnnk = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Bnnk,
            'bnnk_id' => $bnnk?->id ?? Bnnk::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
