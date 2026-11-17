<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Actions\AnalyzeImages;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisAgent;
use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage;
use CraftCms\Cms\Asset\Analysis\Tasks\ClassifyImage;
use CraftCms\Cms\Asset\Analysis\Tasks\DetectFocalPoint;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateTitles;
use CraftCms\Cms\Asset\Analysis\Tasks\TagImage;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\Ai\CaptionImageTask;
use CraftCms\Cms\Tests\Support\Ai\FocusImageTask;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Image;

use function Pest\Laravel\actingAs;

/**
 * Creates an image asset with a real PNG on its volume’s disk.
 *
 * @param  bool  $sampled  Whether the image’s colors and metadata have been sampled already, so only AI tasks are left
 */
function analyzableImage(?string $alt = null, string $kind = 'image', string $filename = 'photo.png', bool $sampled = true): Asset
{
    if (! app()->bound('analysis.volume')) {
        $disk = Storage::fake('analysis-disk');
        config()->set('filesystems.disks.analysis-disk.root', $disk->path(''));
        app()->instance('analysis.volume', Volume::factory()->create(['fs' => 'analysis-disk']));
    }

    $volume = app('analysis.volume');
    $filename = Str::random(8).'-'.$filename;
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => app(Folders::class)->getRootFolderByVolumeId($volume->id)->id,
        'kind' => $kind,
        'filename' => $filename,
    ]);
    Storage::disk('analysis-disk')->put($filename, file_get_contents(dirname(__DIR__, 3).'/resources/ai/probes/image/red.png'));

    if ($sampled) {
        AssetModel::whereKey($asset->id)->update(['analysis' => json_encode([
            'colors' => ['dominant' => '#dc1e1e', 'grid' => [['#dc1e1e']], 'palette' => ['#dc1e1e'], 'luminance' => [[[0.16, 0.16]]], 'weights' => [1.0]],
            'hash' => '0000000000000000',
            'metadata' => [],
        ])]);
        $asset = Asset::find()->id($asset->id)->status(null)->one();
    }

    if ($alt !== null) {
        $asset->alt = $alt;
        Elements::saveElement($asset);
    }

    return $asset;
}

function aiImageSettings(array $tasks = []): void
{
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => $tasks,
    ]));
}

beforeEach(function (): void {
    config(['ai.providers' => [
        'openai' => ['driver' => 'openai', 'key' => 'sk-test'],
        'anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test'],
    ]]);
    // These tests are about their own tasks, not Craft’s
    app(AiTasks::class)->remove(GenerateAltText::class, GenerateTitles::class, DetectFocalPoint::class, TagImage::class, ClassifyImage::class);
    app(AiTasks::class)->register(CaptionImageTask::class, FocusImageTask::class);
    aiImageSettings();
});

it('analyzes an image for every task that shares a model in one request, and saves the results', function (): void {
    $asset = analyzableImage();
    ImageAnalysisAgent::fake([['test_caption' => ['caption' => 'A red square'], 'test_focus' => ['x' => 0.25, 'y' => 0.75]]]);

    $applied = app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    ImageAnalysisAgent::assertPromptedTimes(1);
    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => $prompt->attachments->first() instanceof Image
        && str_contains($prompt->prompt, 'Write a caption.')
        && str_contains($prompt->prompt, 'Find the subject.'));
    expect($applied)->toEqualCanonicalizing(['test:caption', 'test:focus'])
        ->and($saved->alt)->toBe('A red square')
        ->and($saved->getFocalPoint())->toBe(['x' => 0.25, 'y' => 0.75]);
});

it('sends a separate request for tasks that use a different model, and saves what succeeded when another fails', function (): void {
    aiImageSettings(['test:focus' => ['provider' => 'anthropic', 'model' => 'claude-vision']]);
    $asset = analyzableImage();
    ImageAnalysisAgent::fake([
        ['test_caption' => ['caption' => 'A red square']],
        fn () => throw new RuntimeException('Overloaded'),
    ]);

    expect(fn () => app(ImageAnalyzer::class)->analyze($asset))->toThrow(RuntimeException::class, 'Overloaded');

    ImageAnalysisAgent::assertPromptedTimes(2);
    expect(Asset::find()->id($asset->id)->one()->alt)->toBe('A red square');
});

it('only runs tasks that are available and have something to do', function (): void {
    aiImageSettings(['test:focus' => ['enabled' => false]]);
    $described = analyzableImage('Already described');
    $document = analyzableImage(kind: 'pdf', filename: 'doc.pdf');
    $analyzer = app(ImageAnalyzer::class);

    expect($analyzer->pendingTasks($described))->toBe([])
        ->and($analyzer->pendingTasks($described, overwrite: true))->toBe(['test:caption'])
        ->and($analyzer->pendingTasks(analyzableImage()))->toBe(['test:caption'])
        ->and($analyzer->pendingTasks($document))->toBe([])
        ->and($analyzer->pendingTasks(analyzableImage(), ['test:focus']))->toBe([]);
});

it('queues images that have something to analyze', function (): void {
    Queue::fake();
    $analyzer = app(ImageAnalyzer::class);

    expect($analyzer->queue(analyzableImage('Described')))->toBeTrue()
        ->and($analyzer->queue(analyzableImage(kind: 'pdf', filename: 'doc.pdf')))->toBeFalse();

    Queue::assertPushed(AnalyzeImage::class, 1);

    aiImageSettings(['test:caption' => ['enabled' => false], 'test:focus' => ['enabled' => false]]);

    expect($analyzer->queue(analyzableImage()))->toBeFalse()
        ->and($analyzer->available())->toBeFalse();
});

it('queues images that are missing their colors or metadata when asked to, without any AI tasks', function (): void {
    Queue::fake();
    aiImageSettings(['test:caption' => ['enabled' => false], 'test:focus' => ['enabled' => false]]);
    $analyzer = app(ImageAnalyzer::class);
    $unsampled = analyzableImage(sampled: false);

    expect($analyzer->needsSampling($unsampled))->toBeTrue()
        ->and($analyzer->needsSampling(analyzableImage()))->toBeFalse()
        ->and($analyzer->queue($unsampled))->toBeFalse()
        ->and($analyzer->queue($unsampled, sample: true))->toBeTrue()
        ->and($analyzer->queue(analyzableImage(), sample: true))->toBeFalse();

    Queue::assertPushed(AnalyzeImage::class, 1);
});

it('samples what an image is missing from its file, filling in its alt text and title from its metadata', function (): void {
    // Craft’s alt text and title tasks fill these in, even when they’re disabled
    app(AiTasks::class)->register(GenerateAltText::class, GenerateTitles::class);
    aiImageSettings(['test:caption' => ['enabled' => false], 'test:focus' => ['enabled' => false], 'craft:altText' => ['enabled' => false], 'craft:title' => ['enabled' => false]]);
    $asset = analyzableImage(filename: 'photo.jpg', sampled: false);
    Storage::disk('analysis-disk')->put($asset->getPath(), file_get_contents(metadataJpeg(xmpPacket(
        '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">Harbor at dusk</rdf:li></rdf:Alt></dc:title>'.
        '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">Boats at dusk</rdf:li></rdf:Alt></dc:description>'
    ))));
    deleteMetadataJpegs();
    $asset->title = AssetsHelper::filename2Title(pathinfo($asset->getFilename(), PATHINFO_FILENAME));
    Elements::saveElement($asset);

    app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    expect($saved->analysis->colors?->grid)->not->toBe([])
        ->and($saved->analysis->colors?->luminance)->not->toBe([])
        ->and($saved->analysis->metadata?->title)->toBe('Harbor at dusk')
        ->and($saved->title)->toBe('Harbor at dusk')
        ->and($saved->alt)->toBe('Boats at dusk')
        ->and(app(ImageAnalyzer::class)->needsSampling($saved))->toBeFalse();
});

it('doesn’t replace the alt text and title an image’s embedded metadata fills in with generated ones', function (): void {
    app(AiTasks::class)->register(GenerateAltText::class, GenerateTitles::class);
    aiImageSettings(['test:caption' => ['enabled' => false], 'test:focus' => ['enabled' => false]]);
    $asset = analyzableImage(filename: 'photo.jpg', sampled: false);
    Storage::disk('analysis-disk')->put($asset->getPath(), file_get_contents(metadataJpeg(xmpPacket(
        '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">Harbor at dusk</rdf:li></rdf:Alt></dc:title>'.
        '<Iptc4xmpCore:AltTextAccessibility><rdf:Alt><rdf:li xml:lang="x-default">Fishing boats moored at dusk</rdf:li></rdf:Alt></Iptc4xmpCore:AltTextAccessibility>'
    ))));
    deleteMetadataJpegs();
    $asset->title = AssetsHelper::filename2Title(pathinfo($asset->getFilename(), PATHINFO_FILENAME));
    Elements::saveElement($asset);
    ImageAnalysisAgent::fake([['craft_altText' => ["site{$asset->siteId}" => 'A red square'], 'craft_title' => ["site{$asset->siteId}" => 'Red square']]]);

    $applied = app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    expect($saved->alt)->toBe('Fishing boats moored at dusk')
        ->and($saved->title)->toBe('Harbor at dusk')
        ->and($applied)->toBe([]);
    ImageAnalysisAgent::assertNeverPrompted();
});

it('queues new images for files already in place, which couldn’t be sampled when they were saved', function (): void {
    Queue::fake();
    aiImageSettings(['test:caption' => ['enabled' => false], 'test:focus' => ['enabled' => false]]);
    $volume = Volume::factory()->create(['fs' => 'analysis-disk-new']);
    Storage::fake('analysis-disk-new')->put('placed.png', file_get_contents(dirname(__DIR__, 3).'/resources/ai/probes/image/red.png'));
    app(Volumes::class)->reset();

    // A file that's already where it belongs, as a direct upload leaves it
    $asset = new Asset;
    $asset->setFilename('placed.png');
    $asset->setVolumeId($volume->id);
    $asset->folderId = app(Folders::class)->getRootFolderByVolumeId($volume->id)->id;
    $asset->setWidth(1);
    $asset->setHeight(1);
    $asset->ruleset->useScenario(AssetRules::SCENARIO_INDEX);

    expect(Elements::saveElement($asset))->toBeTrue();
    Queue::assertPushed(AnalyzeImage::class, fn (AnalyzeImage $job): bool => $job->assetId === $asset->id);
});

it('queues images to be analyzed when their file is replaced', function (): void {
    Queue::fake();
    $asset = analyzableImage();
    $replacement = AssetsHelper::tempFilePath('png');
    copy(dirname(__DIR__, 3).'/resources/ai/probes/image/blue.png', $replacement);

    app(Assets::class)->replaceAssetFile($asset, $replacement, $asset->getFilename());

    Queue::assertPushed(AnalyzeImage::class, fn (AnalyzeImage $job): bool => $job->assetId === $asset->id && ! $job->overwrite);
});

it('queues the selected images that are missing something with the bulk action', function (): void {
    Queue::fake();
    actingAs(User::find()->admin()->one());
    $missing = analyzableImage();
    $described = analyzableImage('Described');
    aiImageSettings(['test:focus' => ['enabled' => false]]);
    $action = new AnalyzeImages;

    $action->performAction(Asset::find()->id([$missing->id, $described->id]));

    Queue::assertPushed(AnalyzeImage::class, 1);
    Queue::assertPushed(AnalyzeImage::class, fn (AnalyzeImage $job): bool => $job->assetId === $missing->id);
    expect($action->getMessage())->toBe('Analyzing 1 image.');
});
