<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

final class AssetFactory extends Factory
{
    protected $model = Asset::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', \CraftCms\Cms\Element\Elements\Asset::class),
            'volumeId' => Volume::factory(),
            'folderId' => VolumeFolder::factory(),
            'filename' => fake()->word().'.jpg',
            'kind' => 'image',
        ];
    }

    #[\Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Asset $asset) {
            // For some reason the element factory doesn't get saved properly
            if ($asset->id === 0) {
                $asset->update([
                    'id' => Element::query()
                        ->where('type', \CraftCms\Cms\Element\Elements\Asset::class)
                        ->latest('id')
                        ->first()
                        ->id,
                ]);
            }
        });
    }
}
