<?php

namespace adevesa\SimpleOTP\Database\Factories;

use adevesa\SimpleOTP\Models\SimpleOTP;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpleOTPFactory extends Factory
{
    protected $model = SimpleOTP::class;

    public function definition()
    {
        return [
            'identity' => $this->faker->email,
            'code' => $this->faker->randomNumber(6),
            'validated_at' => null,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ];
    }
}
