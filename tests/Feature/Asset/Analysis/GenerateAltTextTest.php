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
use CraftCms\Cms\FieldLayout\LayoutElements\Assets\AltField;
use CraftCms\Cms\Http\Controllers\Assets\GenerateAltTextController;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config()->set('filesystems.disks.alt-text-test', ['driver' => 'local', 'root' => storage_path('framework/testing/alt-text-test')]);
    config(['ai.providers' => ['openai' => ['driver' => 'openai', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => ['craft:title' => ['enabled' => false], 'craft:focalPoint' => ['enabled' => false], 'craft:tagImage' => ['enabled' => false]],
    ]));
    $this->primarySite = Sites::getPrimarySite();
    $this->germanSite = Site::factory()->create(['handle' => 'german', 'language' => 'de']);
    Sites::refreshSites();
});

/** Creates an image asset in every site, with a real PNG on its volume’s disk. */
function altTextImage(string $altTranslationMethod, array $alt = []): Asset
{
    $volume = VolumeModel::factory()->create([
        'fs' => 'alt-text-test',
        'propagationMethod' => PropagationMethod::All->value,
        'altTranslationMethod' => $altTranslationMethod,
    ]);
    app(Volumes::class)->reset();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'kind' => 'image',
        'filename' => uniqid('photo-').'.png',
    ]);
    Elements::saveElement($asset);
    Storage::disk('alt-text-test')->put($asset->getPath(), file_get_contents(dirname(__DIR__, 4).'/resources/ai/probes/image/red.png'));

    foreach ($alt as $siteId => $text) {
        $siteAsset = Asset::find()->id($asset->id)->siteId($siteId)->status(null)->one();
        $siteAsset->alt = $text;
        Elements::saveElement($siteAsset);
    }

    return Asset::find()->id($asset->id)->status(null)->one();
}

function altTextIn(Asset $asset, int $siteId): ?string
{
    return Asset::find()->id($asset->id)->siteId($siteId)->status(null)->one()->alt;
}

it('is a core image analysis task', function (): void {
    expect(app(AiTasks::class)->getTaskByHandle('craft:altText'))->not->toBeNull()
        ->and(array_keys(app(ImageAnalyzer::class)->tasks()))->toContain('craft:altText');
});

it('writes alt text in the language of each site with its own alt text', function (): void {
    $asset = altTextImage('site');
    ImageAnalysisAgent::fake([['craft_altText' => [
        "site{$this->primarySite->id}" => 'A red square',
        "site{$this->germanSite->id}" => 'Ein rotes Quadrat',
    ]]]);

    app(ImageAnalyzer::class)->analyze($asset);

    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, "site{$this->germanSite->id}: German (de)"));
    expect(altTextIn($asset, $this->primarySite->id))->toBe('A red square')
        ->and(altTextIn($asset, $this->germanSite->id))->toBe('Ein rotes Quadrat');
});

it('writes one alt text for sites that share it', function (): void {
    $asset = altTextImage('none');
    ImageAnalysisAgent::fake([['craft_altText' => ["site{$this->primarySite->id}" => 'A red square']]]);

    app(ImageAnalyzer::class)->analyze($asset);

    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => ! str_contains($prompt->prompt, 'German'));
    expect(altTextIn($asset, $this->primarySite->id))->toBe('A red square')
        ->and(altTextIn($asset, $this->germanSite->id))->toBe('A red square');
});

it('only fills in missing alt text, unless asked to overwrite it', function (bool $overwrite, string $expectedPrimary): void {
    $asset = altTextImage('site', [Sites::getPrimarySite()->id => 'Hand-written']);
    ImageAnalysisAgent::fake([['craft_altText' => [
        "site{$this->primarySite->id}" => 'A red square',
        "site{$this->germanSite->id}" => 'Ein rotes Quadrat',
    ]]]);

    app(ImageAnalyzer::class)->analyze($asset, overwrite: $overwrite);

    expect(altTextIn($asset, $this->primarySite->id))->toBe($expectedPrimary)
        ->and(altTextIn($asset, $this->germanSite->id))->toBe('Ein rotes Quadrat');
})->with([
    'filling in' => [false, 'Hand-written'],
    'overwriting' => [true, 'A red square'],
]);

it('has nothing to do once every site has alt text', function (): void {
    $asset = altTextImage('none', [Sites::getPrimarySite()->id => 'Hand-written']);

    expect(app(ImageAnalyzer::class)->pendingTasks($asset))->toBe([])
        ->and(app(ImageAnalyzer::class)->pendingTasks($asset, overwrite: true))->toBe(['craft:altText']);
});

it('suggests alt text for the alt text field without saving it', function (): void {
    actingAs(User::find()->admin()->one());
    $asset = altTextImage('none');
    ImageAnalysisAgent::fake([['craft_altText' => ["site{$this->primarySite->id}" => 'A red square']]]);

    postJson(action(GenerateAltTextController::class), ['assetId' => $asset->id, 'siteId' => $this->primarySite->id])
        ->assertOk()
        ->assertJsonPath('value', 'A red square');

    expect(altTextIn($asset, $this->primarySite->id))->toBeNull();
});

it('only suggests alt text to people who can save the asset', function (): void {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->create(['admin' => false]));
    $asset = altTextImage('none');

    postJson(action(GenerateAltTextController::class), ['assetId' => $asset->id, 'siteId' => $this->primarySite->id])
        ->assertForbidden();
});

it('offers to generate alt text from the alt text field once it’s set up', function (bool $enabled): void {
    actingAs(User::find()->admin()->one());
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai']],
        'tasks' => ['craft:title' => ['enabled' => false], 'craft:altText' => ['enabled' => $enabled], 'craft:focalPoint' => ['enabled' => false], 'craft:tagImage' => ['enabled' => false]],
    ]));
    $asset = altTextImage('none');
    $field = new AltField;
    $items = (fn (): array => $this->actionMenuItems($asset))->call($field);
    $generate = array_find($items, fn (array $item): bool => ($item['label'] ?? null) === 'Generate alt text');

    expect($generate !== null)->toBe($enabled);

    if ($enabled) {
        expect($generate['action'])->toMatchArray(['type' => 'event', 'name' => 'craft:generate-field-value'])
            ->and($generate['action']['detail']['params'])->toBe(['assetId' => $asset->id, 'siteId' => $asset->siteId]);
    }
})->with(['set up' => true, 'disabled' => false]);
