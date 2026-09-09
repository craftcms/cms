<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Asset\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class VolumeFactory extends Factory
{
    #[Override]
    protected $model = Volume::class;

    #[Override]
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'handle' => fake()->slug(),
            'fs' => 'disk:local',
        ];
    }
}
