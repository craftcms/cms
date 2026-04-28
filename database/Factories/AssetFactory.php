<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Factories;

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Element\Models\Element;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

class AssetFactory extends Factory
{
    #[Override]
    protected $model = Asset::class;

    #[Override]
    public function definition(): array
    {
        return [
            'id' => Element::factory()->set('type', \CraftCms\Cms\Asset\Elements\Asset::class),
            'volumeId' => Volume::factory(),
            'folderId' => VolumeFolder::factory(),
            'filename' => fake()->word().'.jpg',
            'kind' => 'image',
        ];
    }

    public function createElement(array $attributes = []): \CraftCms\Cms\Asset\Elements\Asset
    {
        $model = $this->create($attributes);

        return \CraftCms\Cms\Asset\Elements\Asset::find()->id($model->id)->one();
    }

    #[Override]
    public function configure(): self
    {
        return $this->afterCreating(function (Asset $asset) {
            // For some reason the element factory doesn't get saved properly
            if ($asset->id === 0) {
                $asset->update([
                    'id' => Element::query()
                        ->where('type', \CraftCms\Cms\Asset\Elements\Asset::class)
                        ->latest('id')
                        ->first()
                        ->id,
                ]);
            }
        });
    }
}
