<?php

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Plugin\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    #[\Override]
    public function definition()
    {
        return [
            'uid' => $this->faker->uuid(),
            'handle' => $this->faker->slug(),
            'version' => $this->faker->semver(),
            'schemaVersion' => $this->faker->semver(),
            'installDate' => $this->faker->dateTime(),
        ];
    }
}
