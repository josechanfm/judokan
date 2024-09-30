<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

class MemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name_zh' => $this->faker->firstName(),
            'name_fn' => $this->faker->lastName(),
            'display_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
