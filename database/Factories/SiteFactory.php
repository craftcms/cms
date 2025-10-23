<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Models\SiteGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SiteFactory extends Factory
{
    protected $model = Site::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'groupId' => SiteGroup::factory(),
            'handle' => $this->faker->slug(),
            'name' => $this->faker->words(asText: true),
            'primary' => false,
            'language' => $this->faker->locale(),
            'sortOrder' => $this->faker->numberBetween(1, 100),
        ];
    }
}
