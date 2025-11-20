<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Asset\Models\Asset;
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
            'folderId' => VolumeFolder::factory(),
            'filename' => fake()->word().'.jpg',
            'kind' => 'image',
        ];
    }

    #[\Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Asset $asset) {
            $asset->element->update([
                'dateCreated' => $asset->dateCreated,
                'dateUpdated' => $asset->dateCreated,
            ]);

            $asset->update(['volumeId' => $asset->folder->volume?->id]);
        });
    }
}
