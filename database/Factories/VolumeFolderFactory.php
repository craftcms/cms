<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class VolumeFolderFactory extends Factory
{
    protected $model = VolumeFolder::class;

    #[Override]
    public function definition(): array
    {
        return [
            'volumeId' => Volume::factory(),
            'name' => fake()->word(),
        ];
    }
}
