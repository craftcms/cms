<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisAgent;
use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('filesystems.disks.titles-test', ['driver' => 'local', 'root' => storage_path('framework/testing/titles-test')]);
    config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => ['craft:altText' => ['enabled' => false], 'craft:focalPoint' => ['enabled' => false], 'craft:tagImage' => ['enabled' => false]],
    ]));
    $this->primarySite = Sites::getPrimarySite();
    $this->germanSite = Site::factory()->create(['handle' => 'german', 'language' => 'de']);
    Sites::refreshSites();
});

/**
 * Creates an image asset in every site, titled after its filename, with a real PNG on its volume’s disk.
 *
 * @param  array<int, string>  $titles  Titles to give it in some sites, by site ID
 */
function titlesImage(string $titleTranslationMethod, array $titles = []): Asset
{
    $volume = VolumeModel::factory()->create([
        'fs' => 'titles-test',
        'propagationMethod' => PropagationMethod::All->value,
        'titleTranslationMethod' => $titleTranslationMethod,
    ]);
    app(Volumes::class)->reset();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'kind' => 'image',
        'filename' => 'IMG_4759.png',
    ]);
    $asset->title = 'IMG 4759';
    Elements::saveElement($asset);
    Storage::disk('titles-test')->put($asset->getPath(), file_get_contents(dirname(__DIR__, 4).'/resources/ai/probes/image/red.png'));

    foreach ($titles as $siteId => $title) {
        $siteAsset = Asset::find()->id($asset->id)->siteId($siteId)->status(null)->one();
        $siteAsset->title = $title;
        Elements::saveElement($siteAsset);
    }

    return Asset::find()->id($asset->id)->status(null)->one();
}

function titleIn(Asset $asset, int $siteId): ?string
{
    return Asset::find()->id($asset->id)->siteId($siteId)->status(null)->one()->title;
}

it('is a core image analysis task', function (): void {
    expect(app(AiTasks::class)->getTaskByHandle('craft:title'))->not->toBeNull()
        ->and(array_keys(app(ImageAnalyzer::class)->tasks()))->toContain('craft:title');
});

it('titles images in the language of each site with its own title', function (): void {
    $asset = titlesImage('site');
    ImageAnalysisAgent::fake([['craft_title' => [
        "site{$this->primarySite->id}" => 'Red square',
        "site{$this->germanSite->id}" => 'Rotes Quadrat',
    ]]]);

    app(ImageAnalyzer::class)->analyze($asset);

    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, "site{$this->germanSite->id}: German (de)"));
    expect(titleIn($asset, $this->primarySite->id))->toBe('Red square')
        ->and(titleIn($asset, $this->germanSite->id))->toBe('Rotes Quadrat');
});

it('writes one title for sites that share it', function (): void {
    $asset = titlesImage('none');
    ImageAnalysisAgent::fake([['craft_title' => ["site{$this->primarySite->id}" => 'Red square']]]);

    app(ImageAnalyzer::class)->analyze($asset);

    expect(titleIn($asset, $this->primarySite->id))->toBe('Red square')
        ->and(titleIn($asset, $this->germanSite->id))->toBe('Red square');
});

it('only replaces titles given from the filename, unless asked to overwrite them', function (bool $overwrite, string $expectedPrimary): void {
    $asset = titlesImage('site', [Sites::getPrimarySite()->id => 'Hand-written']);
    ImageAnalysisAgent::fake([['craft_title' => [
        "site{$this->primarySite->id}" => 'Red square',
        "site{$this->germanSite->id}" => 'Rotes Quadrat',
    ]]]);

    app(ImageAnalyzer::class)->analyze($asset, overwrite: $overwrite);

    expect(titleIn($asset, $this->primarySite->id))->toBe($expectedPrimary)
        ->and(titleIn($asset, $this->germanSite->id))->toBe('Rotes Quadrat');
})->with([
    'filling in' => [false, 'Hand-written'],
    'overwriting' => [true, 'Red square'],
]);

it('has nothing to do once every site has a title of its own', function (): void {
    $asset = titlesImage('none', [Sites::getPrimarySite()->id => 'Hand-written']);

    expect(app(ImageAnalyzer::class)->pendingTasks($asset))->toBe([])
        ->and(app(ImageAnalyzer::class)->pendingTasks($asset, overwrite: true))->toBe(['craft:title']);
});
