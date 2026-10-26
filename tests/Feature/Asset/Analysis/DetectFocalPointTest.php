<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisAgent;
use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('filesystems.disks.focal-point-test', ['driver' => 'local', 'root' => storage_path('framework/testing/focal-point-test')]);
    config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => ['craft:altText' => ['enabled' => false]],
    ]));
});

function focalPointImage(?array $focalPoint = null): Asset
{
    $volume = VolumeModel::factory()->create(['fs' => 'focal-point-test']);
    app(Volumes::class)->reset();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'kind' => 'image',
        'filename' => uniqid('photo-').'.png',
    ]);
    Storage::disk('focal-point-test')->put($asset->getPath(), file_get_contents(dirname(__DIR__, 4).'/resources/ai/probes/image/red.png'));

    if ($focalPoint !== null) {
        $asset->setFocalPoint($focalPoint);
        Elements::saveElement($asset);
    }

    return Asset::find()->id($asset->id)->status(null)->one();
}

it('sets the focal point, kept within the image, and invalidates its transforms', function (array $result, array $expected): void {
    $asset = focalPointImage();
    ImageAnalysisAgent::fake([['craft_focalPoint' => $result]]);
    Event::fake([AssetTransformsInvalidating::class]);

    app(ImageAnalyzer::class)->analyze($asset);

    expect(Asset::find()->id($asset->id)->one()->getFocalPoint())->toBe($expected);
    Event::assertDispatched(AssetTransformsInvalidating::class);
})->with([
    'inside' => [['x' => 0.3, 'y' => 0.6], ['x' => 0.3, 'y' => 0.6]],
    'outside' => [['x' => -0.2, 'y' => 1.4], ['x' => 0.0, 'y' => 1.0]],
]);

it('only sets the focal points of images without one, unless asked to overwrite it', function (): void {
    $asset = focalPointImage(['x' => 0.1, 'y' => 0.1]);
    ImageAnalysisAgent::fake([['craft_focalPoint' => ['x' => 0.7, 'y' => 0.4]]]);

    expect(app(ImageAnalyzer::class)->pendingTasks($asset))->toBe([]);

    app(ImageAnalyzer::class)->analyze($asset, overwrite: true);

    expect(Asset::find()->id($asset->id)->one()->getFocalPoint())->toBe(['x' => 0.7, 'y' => 0.4]);
});
