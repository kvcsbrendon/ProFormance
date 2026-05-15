<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\LoginDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name'         => $this->faker->firstName(),
            'last_name'          => $this->faker->lastName(),
            'country_phone_code' => 44,
            'phone_number'       => $this->faker->numerify('07########'),
            'user_role'          => 'customer',
            'is_active'          => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            LoginDetail::create([
                'user_id'       => $user->user_id,
                'email_address' => $this->faker->unique()->safeEmail(),
                'password_hash' => Hash::make('TestPass!99'),
                'password_salt' => '',
            ]);
        });
    }

    public function admin(): static
    {
        return $this->state(fn () => ['user_role' => 'admin']);
    }
}