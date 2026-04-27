<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class UserGroupFactory extends Factory
{
    #[Override]
    protected $model = UserGroup::class;

    #[Override]
    public function definition(): array
    {
        return [
            'uid' => fake()->uuid(),
            'name' => fake()->words(asText: true),
            'handle' => fake()->slug(),
            'description' => fake()->paragraph(),
            'dateCreated' => $date = fake()->dateTime(),
            'dateUpdated' => $date,
        ];
    }
}
