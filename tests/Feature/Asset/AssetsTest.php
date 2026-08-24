<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Events\PreviewHandlerResolving;
use CraftCms\Cms\Asset\Events\ThumbUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetProcessorNotFoundException;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\PreviewHandlers\Text;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\Assets as AssetsFacade;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->assets = app(Assets::class);

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/assets-test/test-disk'),
    ]);
});

it('is a singleton', function () {
    expect(AssetsFacade::getFacadeRoot())->toBe(app(Assets::class));
    expect($this->assets)->toBe(app(Assets::class));
});

it('can get an asset by id', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $assetModel = AssetModel::factory()->create([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    $asset = $this->assets->getAssetById($assetModel->id);

    expect($asset)->toBeInstanceOf(Asset::class);
});

it('returns null for non-existent asset id', function () {
    expect($this->assets->getAssetById(999))->toBeNull();
});

it('can get total assets', function () {
    expect($this->assets->getTotalAssets())->toBe(0);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    AssetModel::factory()->count(3)->create([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    expect($this->assets->getTotalAssets())->toBe(3);
});

it('dispatches ThumbUrlResolving event in getThumbUrl', function () {
    Event::fake([ThumbUrlResolving::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => 'text',
    ]);

    $this->assets->getThumbUrl($asset, 100);

    Event::assertDispatched(fn (ThumbUrlResolving $event) => $event->asset->id === $asset->id
        && $event->width === 100
        && $event->height === 100);
});

it('uses ThumbUrlResolving event url when set', function () {
    Event::listen(ThumbUrlResolving::class, function (ThumbUrlResolving $event) {
        $event->url = 'https://example.com/custom-thumb.jpg';
    });

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.jpg',
        'kind' => 'image',
    ]);

    $url = $this->assets->getThumbUrl($asset, 100);

    expect($url)->toBe('https://example.com/custom-thumb.jpg');
});

it('renders non-image thumbnails and previews through the selected driver', function () {
    $driver = new ControlPanelAssetProcessorDriver;
    registerControlPanelTransformer($driver);
    Cms::config()->defaultAssetProcessor('test');
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.pdf',
        'kind' => FileKind::Pdf->value,
        'width' => 600,
        'height' => 800,
    ]);

    expect($this->assets->getThumbUrl($asset, 120, 80))->toBe('/renditions/120x80.webp')
        ->and($this->assets->getImagePreviewUrl($asset, 1000, 1000))->toBe('/renditions/1000x1000.webp')
        ->and(array_column($driver->requests, 'operations'))->toBe([
            ['height' => 80, 'mode' => 'crop', 'width' => 120],
            ['height' => 1000, 'mode' => 'crop', 'width' => 1000],
        ])
        ->and(array_map(fn (AssetTransformRequest $request): bool => $request->immediately, $driver->requests))->toBe([false, true]);
});

it('uses file-kind images only when the selected driver does not support the source', function () {
    registerControlPanelTransformer(new ControlPanelAssetProcessorDriver(
        new NotSupportedException('unsupported'),
    ));
    Cms::config()->defaultAssetProcessor('test');
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.pdf',
        'kind' => FileKind::Pdf->value,
    ]);
    $iconUrl = Url::actionUrl('assets/icon', ['extension' => 'pdf']);

    expect($this->assets->getThumbUrl($asset, 100))->toBe($iconUrl)
        ->and($this->assets->getThumbUrl($asset, 100, iconFallback: false))->toBeNull()
        ->and($this->assets->getImagePreviewUrl($asset, 1000, 1000))->toBe($iconUrl);
});

it('does not disguise control-panel transform configuration failures as file-kind images', function () {
    Cms::config()->defaultAssetProcessor('missing');
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.pdf',
        'kind' => FileKind::Pdf->value,
    ]);

    expect(fn () => $this->assets->getThumbUrl($asset, 100))
        ->toThrow(AssetProcessorNotFoundException::class)
        ->and(fn () => $this->assets->getImagePreviewUrl($asset, 1000, 1000))
        ->toThrow(AssetProcessorNotFoundException::class);
});

it('dispatches PreviewHandlerResolving event', function () {
    Event::fake([PreviewHandlerResolving::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => 'text',
    ]);

    $this->assets->getAssetPreviewHandler($asset);

    Event::assertDispatched(PreviewHandlerResolving::class);
});

it('returns default preview handler for known asset kinds', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);

    $textAsset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.txt',
        'kind' => FileKind::Text->value,
    ]);

    $handler = $this->assets->getAssetPreviewHandler($textAsset);

    expect($handler)->toBeInstanceOf(Text::class);
});

it('returns null preview handler for unknown asset kinds', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);

    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'test.xyz',
        'kind' => 'unknown',
    ]);

    $handler = $this->assets->getAssetPreviewHandler($asset);

    expect($handler)->toBeNull();
});

it('can get temp asset upload filesystem', function () {
    $fs = $this->assets->getTempAssetUploadFs();

    expect($fs)->toBeInstanceOf(FsInterface::class);
});

it('can create a temp asset query', function () {
    $query = $this->assets->createTempAssetQuery();

    expect($query)->toBeInstanceOf(AssetQuery::class);
});

it('can get name replacement in folder when no conflict', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);

    app()->forgetInstance(Folders::class);

    $rootFolder = app(Folders::class)->getRootFolderByVolumeId($volume->id);

    $result = $this->assets->getNameReplacementInFolder('unique-file.jpg', $rootFolder->id);

    expect($result)->toBe('unique-file.jpg');
});

it('dispatches AssetReplacing event with filename', function () {
    Event::fake([AssetReplacing::class]);

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    $tempFile = tempnam(sys_get_temp_dir(), 'craft_test_');
    file_put_contents($tempFile, 'test content');

    try {
        $this->assets->replaceAssetFile($asset, $tempFile, 'new-filename.jpg');
    } catch (Throwable) {
        // The save may fail due to missing filesystem setup, but the event should still fire
    }

    Event::assertDispatched(fn (AssetReplacing $event) => $event->asset->id === $asset->id
        && $event->filename === 'new-filename.jpg');

    @unlink($tempFile);
});

it('invalidates Asset Transforms once for each source lifecycle operation', function (Closure $operation) {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $rootFolder = app(Folders::class)->getRootFolderByVolumeId($volume->id);
    $folder = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $rootFolder->id,
        'path' => 'source/',
    ]);
    $destination = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $rootFolder->id,
        'path' => 'destination/',
    ]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => fake()->uuid().'.txt',
        'kind' => FileKind::Text->value,
    ]);
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), 'source');
    Event::fake([AssetTransformsInvalidating::class]);

    $operation($this->assets, $asset, $destination->id);

    Event::assertDispatchedTimes(AssetTransformsInvalidating::class, 1);
})->with([
    'replacement' => [function (Assets $assets, Asset $asset): void {
        $replacement = tempnam(sys_get_temp_dir(), 'craft-asset-replacement-');
        file_put_contents($replacement, 'replacement');
        $assets->replaceAssetFile($asset, $replacement, $asset->getFilename());
    }],
    'movement' => [function (Assets $assets, Asset $asset, int $destinationId): void {
        $result = $assets->moveAsset($asset, app(Folders::class)->getFolderById($destinationId));

        expect($result)->toBeTrue();
    }],
    'renaming' => [function (Assets $assets, Asset $asset): void {
        $assets->moveAsset($asset, $asset->getFolder(), 'renamed-'.$asset->getFilename());
    }],
    'deletion' => [function (Assets $assets, Asset $asset): void {
        app(Elements::class)->deleteElement($asset, true);
    }],
]);

it('resets caches', function () {
    $this->assets->reset();

    // No error means the reset worked
    expect(true)->toBeTrue();
});

function registerControlPanelTransformer(ControlPanelAssetProcessorDriver $driver): void
{
    app(AssetProcessorDrivers::class)->extend('test', fn () => $driver);
    app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Test',
        'handle' => 'test',
        'driver' => 'test',
    ]), false);
}

class ControlPanelAssetProcessorDriver implements AssetProcessorDriver
{
    public array $requests = [];

    public function __construct(
        private readonly ?Throwable $failure = null,
    ) {}

    public function definition(): AssetProcessorDriverDefinition
    {
        return new AssetProcessorDriverDefinition('Control panel test');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->requests[] = $request;

        return new AssetTransformResult(
            url: sprintf('/renditions/%sx%s.webp', $request->operations['width'], $request->operations['height']),
            mimeType: 'image/webp',
        );
    }
}
