<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisAgent;
use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\ImageType;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('filesystems.disks.classify-image-test', ['driver' => 'local', 'root' => storage_path('framework/testing/classify-image-test')]);
    config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
    aiClassifySettings();
});

/**
 * @param  array<string, array<string, mixed>>  $tasks
 */
function aiClassifySettings(array $tasks = []): void
{
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => [
            'craft:title' => ['enabled' => false],
            'craft:altText' => ['enabled' => false],
            'craft:focalPoint' => ['enabled' => false],
            'craft:tagImage' => ['enabled' => false],
            ...$tasks,
        ],
    ]));
}

/**
 * @param  array<string, mixed>|null  $analysis
 */
function classifiableImage(?array $analysis = null): Asset
{
    $volume = VolumeModel::factory()->create(['fs' => 'classify-image-test']);
    app(Volumes::class)->reset();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'kind' => 'image',
        'filename' => uniqid('photo-').'.png',
    ]);
    Storage::disk('classify-image-test')->put($asset->getPath(), file_get_contents(dirname(__DIR__, 4).'/resources/ai/probes/image/red.png'));

    if ($analysis !== null) {
        AssetModel::whereKey($asset->id)->update(['analysis' => json_encode($analysis)]);
    }

    return Asset::find()->id($asset->id)->status(null)->one();
}

it('is a core image analysis task', function (): void {
    expect(app(AiTasks::class)->getTaskByHandle('craft:imageType'))->not->toBeNull()
        ->and(array_keys(app(ImageAnalyzer::class)->tasks()))->toContain('craft:imageType');
});

it('finds what kind of image an image is, keeps what else was found, and makes it searchable', function (): void {
    $asset = classifiableImage(['colors' => ['dominant' => '#c81e28', 'grid' => [['#c81e28']], 'palette' => ['#c81e28'], 'luminance' => [[[0.134, 0.134]]], 'weights' => [1.0]]]);
    ImageAnalysisAgent::fake([['craft_imageType' => ['type' => 'screenshot']]]);

    app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, '- diagram: a chart, graph'));
    expect($saved->analysis->type)->toBe(ImageType::Screenshot)
        ->and($saved->analysis->colors?->dominant)->toBe('#c81e28')
        ->and($saved->getSearchKeywords('imageType'))->toBe('screenshot')
        ->and($saved->getSearchKeywords('keywords'))->toBe('');
});

it('makes images filterable by their type', function (): void {
    $logo = classifiableImage();
    $screenshot = classifiableImage();
    $unclassified = classifiableImage();
    ImageAnalysisAgent::fake([
        ['craft_imageType' => ['type' => 'logo']],
        ['craft_imageType' => ['type' => 'screenshot']],
    ]);

    app(ImageAnalyzer::class)->analyze($logo);
    app(ImageAnalyzer::class)->analyze($screenshot);
    $ids = fn (mixed $value): array => Asset::find()->id([$logo->id, $screenshot->id, $unclassified->id])->imageType($value)->orderBy('id')->ids();

    expect($ids('logo'))->toBe([$logo->id])
        ->and($ids(ImageType::Screenshot))->toBe([$screenshot->id])
        ->and($ids(['logo', 'screenshot']))->toBe([$logo->id, $screenshot->id])
        ->and($ids(['not', 'logo']))->toBe([$screenshot->id])
        ->and($ids(':empty:'))->toBe([$unclassified->id])
        ->and(Asset::find()->id($logo->id)->one()->getImageType())->toBe(ImageType::Logo)
        ->and(Asset::find()->id($logo->id)->one()->getSearchKeywords('keywords'))->toBe('');
});

it('filters images by their type in GraphQL', function (): void {
    $logo = classifiableImage(['type' => 'logo']);
    classifiableImage(['type' => 'photo']);
    gqlActivateFullAccessSchema();

    graphQL('{ assets(imageType: ["logo"]) { id } }')
        ->assertOk()
        ->assertJsonPath('data.assets', [['id' => (string) $logo->id]]);
});

it('ignores types it doesn’t know', function (): void {
    $asset = classifiableImage();
    ImageAnalysisAgent::fake([['craft_imageType' => ['type' => 'selfie']]]);

    app(ImageAnalyzer::class)->analyze($asset);

    expect(Asset::find()->id($asset->id)->one()->analysis?->type)->toBeNull();
});

it('only classifies images that haven’t been, unless asked to overwrite', function (bool $overwrite, ImageType $expected): void {
    $asset = classifiableImage(['type' => 'photo']);
    ImageAnalysisAgent::fake([['craft_imageType' => ['type' => 'illustration']]]);

    expect(app(ImageAnalyzer::class)->pendingTasks($asset, overwrite: $overwrite))->toBe($overwrite ? ['craft:imageType'] : []);

    app(ImageAnalyzer::class)->analyze($asset, overwrite: $overwrite);

    expect(Asset::find()->id($asset->id)->one()->analysis->type)->toBe($expected);
})->with([
    'filling in' => [false, ImageType::Photo],
    'overwriting' => [true, ImageType::Illustration],
]);

it('keeps what other tasks found in the same request', function (): void {
    aiClassifySettings(['craft:tagImage' => ['enabled' => true]]);
    $asset = classifiableImage();
    ImageAnalysisAgent::fake([[
        'craft_tagImage' => ['keywords' => ['dashboard'], 'text' => 'Revenue'],
        'craft_imageType' => ['type' => 'screenshot'],
    ]]);

    app(ImageAnalyzer::class)->analyze($asset);
    $analysis = Asset::find()->id($asset->id)->one()->analysis;

    expect($analysis->type)->toBe(ImageType::Screenshot)
        ->and($analysis->keywords)->toBe(['dashboard'])
        ->and($analysis->text)->toBe('Revenue');
});

it('exposes what kind of image an image is to GraphQL', function (): void {
    $asset = classifiableImage(['type' => 'diagram']);
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { analysis { type } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.analysis.type', 'diagram');
});
