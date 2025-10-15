<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Site\Models\SiteGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

final class SiteGroupFactory extends Factory
{
    protected $model = SiteGroup::class;

    #[\Override]
    public function definition(): array
    {
        return [
            'uid' => $this->faker->uuid(),
            'name' => $this->faker->words(asText: true),
        ];
    }
}
