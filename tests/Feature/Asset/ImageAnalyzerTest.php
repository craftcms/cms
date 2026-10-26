<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Actions\AnalyzeImages;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisAgent;
use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\Ai\CaptionImageTask;
use CraftCms\Cms\Tests\Support\Ai\FocusImageTask;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\LocalImage;

use function Pest\Laravel\actingAs;

/** Creates an image asset with a real PNG on its volume’s disk. */
function analyzableImage(?string $alt = null, string $kind = 'image', string $filename = 'photo.png'): Asset
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
    app(AiTasks::class)->remove(GenerateAltText::class);
    app(AiTasks::class)->register(CaptionImageTask::class, FocusImageTask::class);
    aiImageSettings();
});

it('analyzes an image for every task that shares a model in one request, and saves the results', function (): void {
    $asset = analyzableImage();
    ImageAnalysisAgent::fake([['test_caption' => ['caption' => 'A red square'], 'test_focus' => ['x' => 0.25, 'y' => 0.75]]]);

    $applied = app(ImageAnalyzer::class)->analyze($asset);
    $saved = Asset::find()->id($asset->id)->one();

    ImageAnalysisAgent::assertPromptedTimes(1);
    ImageAnalysisAgent::assertPrompted(fn ($prompt): bool => $prompt->attachments->first() instanceof LocalImage
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
