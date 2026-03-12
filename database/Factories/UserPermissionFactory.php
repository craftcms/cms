<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\User\Models\UserPermission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class UserPermissionFactory extends Factory
{
    #[Override]
    protected $model = UserPermission::class;

    #[Override]
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
