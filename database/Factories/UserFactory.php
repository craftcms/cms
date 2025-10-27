<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class UserFactory extends Factory
{
    protected $model = User::class;

    #[\Override]
    public function definition()
    {
        return [
            'id' => Element::factory(),
            'username' => $this->faker->userName(),
        ];
    }
}
