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
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('filesystems.disks.tag-image-test', ['driver' => 'local', 'root' => storage_path('framework/testing/tag-image-test')]);
    config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => ['craft:altText' => ['enabled' => false], 'craft:focalPoint' => ['enabled' => false]],
    ]));
});

function taggableImage(): Asset
{
    $volume = VolumeModel::factory()->create(['fs' => 'tag-image-test']);
    app(Volumes::class)->reset();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'kind' => 'image',
        'filename' => uniqid('photo-').'.png',
    ]);
    Storage::disk('tag-image-test')->put($asset->getPath(), file_get_contents(dirname(__DIR__, 4).'/resources/ai/probes/image/red.png'));

    return Asset::find()->id($asset->id)->status(null)->one();
}

it('tags images with keywords and the text in them, and makes them searchable', function (): void {
    $asset = taggableImage();
    ImageAnalysisAgent::fake([['craft_tagImage' => [
        'keywords' => [' lighthouse ', 'coast', 'lighthouse', '', 'dusk'],
        'visibleText' => "  Keep out\nPrivate property ",
    ]]]);

    app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    expect($saved->keywords)->toBe(['lighthouse', 'coast', 'dusk'])
        ->and($saved->visibleText)->toBe("Keep out\nPrivate property")
        ->and($saved->getSearchKeywords('keywords'))->toBe('lighthouse coast dusk')
        ->and($saved->getSearchKeywords('visibleText'))->toContain('Keep out');
});

it('exposes keywords and visible text to GraphQL', function (): void {
    $asset = taggableImage();
    AssetModel::whereKey($asset->id)->update(['keywords' => json_encode(['coast']), 'visibleText' => 'Pier 9']);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { keywords visibleText } }")
        ->assertOk()
        ->assertJsonPath('data.asset.keywords', ['coast'])
        ->assertJsonPath('data.asset.visibleText', 'Pier 9');
});

it('only tags images that haven’t been tagged, unless asked to overwrite their tags', function (): void {
    $asset = taggableImage();
    // An image with keywords has been tagged, even without any text in it
    AssetModel::whereKey($asset->id)->update(['keywords' => json_encode(['coast']), 'visibleText' => null]);
    $asset = Asset::find()->id($asset->id)->one();
    ImageAnalysisAgent::fake([['craft_tagImage' => ['keywords' => ['harbor'], 'visibleText' => 'Pier 9']]]);

    expect(app(ImageAnalyzer::class)->pendingTasks($asset))->toBe([]);

    app(ImageAnalyzer::class)->analyze($asset, overwrite: true);
    $saved = Asset::find()->id($asset->id)->one();

    expect($saved->keywords)->toBe(['harbor'])
        ->and($saved->visibleText)->toBe('Pier 9');
});
