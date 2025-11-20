<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use craft\fs\Local;
use CraftCms\Cms\Asset\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class VolumeFactory extends Factory
{
    protected $model = Volume::class;

    #[Override]
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'handle' => fake()->slug(),
            'fs' => Local::class,
        ];
    }
}
