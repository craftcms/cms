<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class UserFactory extends Factory
{
    protected $model = User::class;

    #[\Override]
    public function definition()
    {
        return [
            'username' => $this->faker->userName(),
        ];
    }
}
