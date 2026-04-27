<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Site\Models\SiteGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class SiteGroupFactory extends Factory
{
    #[Override]
    protected $model = SiteGroup::class;

    #[Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'name' => $this->faker->words(asText: true),
        ];
    }
}
