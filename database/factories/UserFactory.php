<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fullname' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'service_id' => 'SD-' . fake()->unique()->numberBetween(100, 999),
            'phone_number' => '02' . fake()->numberBetween(10000000, 99999999),
            'is_staff' => true,
            'is_head' => false,
            'is_approved' => true,
            'unit' => 'Crime Scene Support',
            'password' => static::$password ??= Hash::make('password123'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * State for unapproved user account.
     */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    /**
     * State for Unit / Directorate Head account.
     */
    public function head(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_head' => true,
            'is_staff' => false,
            'is_approved' => true,
            'unit' => 'Forensic Directorate',
        ]);
    }
}
