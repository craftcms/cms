<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetFileChange;
use CraftCms\Cms\Asset\Events\AssetFileChanged;
use CraftCms\Cms\Asset\Events\AssetUrlDefined;
use CraftCms\Cms\Asset\Events\FolderRenamed;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidated;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.disks.change-events-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-change-events'),
        'url' => 'https://cdn.example.test/media files',
    ]);
    $this->disk = Storage::disk('change-events-disk');
    $this->disk->deleteDirectory('');

    $this->volume = Volume::factory()->create(['disk' => 'change-events-disk', 'hasUrls' => true]);
    $root = app(Folders::class)->getRootFolderByVolumeId($this->volume->id);
    $this->rootId = $root->id;
    $this->folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $root->id,
        'name' => 'My Photos',
        'path' => 'My Photos/',
    ]);
    $this->disk->makeDirectory('My Photos');

    $this->image = fn (string $filename = 'a b.jpg') => tap(AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folderModel->id,
        'filename' => $filename,
        'kind' => 'image',
        'width' => 1200,
        'height' => 800,
        'dateModified' => now()->subMinute(),
    ]), fn (Asset $asset) => $this->disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    ));

    $this->events = [];
    Event::listen([AssetFileChanged::class, AssetTransformsInvalidated::class, FolderRenamed::class], function (object $e) {
        $this->events[] = $e;
    });
    $this->of = fn (string $class) => array_values(array_filter($this->events, fn ($e) => $e instanceof $class));
});

it('reports a replaced file with its old URL', function () {
    $asset = ($this->image)();
    $temp = AssetsHelper::tempFilePath('jpg');
    copy(dirname(__DIR__, 2).'/_data/assets/files/background.jpg', $temp);

    app(Assets::class)->replaceAssetFile($asset, $temp, 'new name.jpg');

    $changed = ($this->of)(AssetFileChanged::class);
    expect($changed)->toHaveCount(1)
        ->and($changed[0]->change)->toBe(AssetFileChange::Replaced)
        ->and($changed[0]->oldPath)->toBe('My Photos/a b.jpg')
        ->and($changed[0]->urls())->toBe(['https://cdn.example.test/media%20files/My%20Photos/a%20b.jpg'])
        ->and($changed[0]->asset->getFilename())->toBe('new-name.jpg');
});

it('reports a moved file with its old URL, and nothing for new assets', function () {
    $asset = ($this->image)();
    expect(($this->of)(AssetFileChanged::class))->toHaveCount(0);

    $root = app(Folders::class)->getFolderById($this->rootId);
    expect(app(Assets::class)->moveAsset($asset, $root))->toBeTrue();

    $changed = ($this->of)(AssetFileChanged::class);
    expect($changed)->toHaveCount(1)
        ->and($changed[0]->change)->toBe(AssetFileChange::Moved)
        ->and($changed[0]->urls())->toBe(['https://cdn.example.test/media%20files/My%20Photos/a%20b.jpg'])
        ->and($changed[0]->asset->getPath())->toBe('a-b.jpg');
});

it('reports the URL each file was served from, as well as its disk URL', function () {
    $base = 'https://cdn.example.test/media%20files/My%20Photos/a%20b.jpg';
    $asset = ($this->image)();
    $oldTimestamp = $asset->dateModified->getTimestamp();
    Cms::config()->revAssetUrls = true;

    try {
        $temp = AssetsHelper::tempFilePath('jpg');
        copy(dirname(__DIR__, 2).'/_data/assets/files/background.jpg', $temp);
        app(Assets::class)->replaceAssetFile($asset, $temp, 'a b.jpg');
    } finally {
        Cms::config()->revAssetUrls = false;
    }

    // A plugin serves assets from its own URLs
    Event::listen(AssetUrlDefined::class, fn (AssetUrlDefined $e) => $e->url = 'https://img.example.test/'.$e->asset->id.'?w=200');
    $moved = ($this->image)('c.jpg');
    expect(app(Assets::class)->moveAsset($moved, app(Folders::class)->getFolderById($this->rootId)))->toBeTrue();

    $changed = ($this->of)(AssetFileChanged::class);
    expect($changed)->toHaveCount(2)
        ->and($changed[0]->urls())->toBe(["$base?v=$oldTimestamp", $base])
        ->and($changed[1]->urls())->toBe([
            "https://img.example.test/{$moved->id}?w=200",
            'https://cdn.example.test/media%20files/My%20Photos/c.jpg',
        ]);
});

it('reports nothing when a new asset is saved with its folder already set', function () {
    $temp = AssetsHelper::tempFilePath('jpg');
    copy(dirname(__DIR__, 2).'/_data/assets/files/background.jpg', $temp);
    $asset = new Asset;
    $asset->tempFilePath = $temp;
    $asset->setFilename('new.jpg');
    $asset->folderId = $this->folderModel->id;
    $asset->newFolderId = $this->folderModel->id;
    $asset->setVolumeId($this->volume->id);
    $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);

    expect(Elements::saveElement($asset))->toBeTrue()
        ->and($this->disk->exists('My Photos/new.jpg'))->toBeTrue()
        ->and(($this->of)(AssetFileChanged::class))->toHaveCount(0);
});

it('does not report a move that was rolled back', function () {
    $asset = ($this->image)();
    $root = app(Folders::class)->getFolderById($this->rootId);

    DB::beginTransaction();
    app(Assets::class)->moveAsset($asset, $root);
    DB::rollBack();

    expect(($this->of)(AssetFileChanged::class))->toHaveCount(0);
});

it('reports deleted files unless the file is kept', function () {
    $kept = ($this->image)('kept.jpg');
    $kept->keepFileOnDelete = true;
    Elements::deleteElement($kept, true);
    expect(($this->of)(AssetFileChanged::class))->toHaveCount(0);

    $asset = ($this->image)();
    Elements::deleteElement($asset, true);
    $changed = ($this->of)(AssetFileChanged::class);
    expect($changed)->toHaveCount(1)
        ->and($changed[0]->change)->toBe(AssetFileChange::Deleted)
        ->and($changed[0]->urls())->toBe(['https://cdn.example.test/media%20files/My%20Photos/a%20b.jpg']);
});

it('reports a file restored from the trash at the URL it was deleted from', function () {
    config()->set('filesystems.disks.change-events-trash', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-change-events-trash'),
    ]);
    Cms::config()->trashedAssetDisk('change-events-trash');

    try {
        $asset = ($this->image)();
        Elements::deleteElement($asset);
        expect($this->disk->exists('My Photos/a b.jpg'))->toBeFalse();
        $this->events = [];

        expect(Elements::restoreElement(Asset::find()->id($asset->id)->trashed()->one()))->toBeTrue();

        $changed = ($this->of)(AssetFileChanged::class);
        expect($changed)->toHaveCount(1)
            ->and($changed[0]->change)->toBe(AssetFileChange::Restored)
            ->and($changed[0]->urls())->toBe(['https://cdn.example.test/media%20files/My%20Photos/a%20b.jpg'])
            ->and($this->disk->exists('My Photos/a b.jpg'))->toBeTrue();
    } finally {
        Cms::config()->trashedAssetDisk(null);
        Storage::disk('change-events-trash')->deleteDirectory('');
    }
});

it('reports deleted transform files with the URLs they were served from', function () {
    $asset = ($this->image)();
    // The URL as Craft serves it, with spaces in the folder and filename encoded
    $url = app(AssetTransformers::class)->withImmediateTransforms(fn () => $asset->getUrl(['width' => 100]));
    $transformFiles = $this->disk->allFiles('_'.$this->volume->uid);
    // A transform that was never generated has no file
    Queue::fake();
    app(AssetTransformers::class)->transform($asset, ['width' => 50]);
    $this->events = [];

    app(AssetTransformers::class)->invalidate($asset);

    $invalidated = ($this->of)(AssetTransformsInvalidated::class);
    expect($invalidated)->toHaveCount(1)
        ->and($url)->toContain('My%20Photos/')
        ->and($url)->toContain('?v=')
        ->and($invalidated[0]->urls())->toBe([$url, strtok($url, '?')])
        ->and($transformFiles)->toBe([$invalidated[0]->files[0]['path']])
        ->and($this->disk->exists($invalidated[0]->files[0]['path']))->toBeFalse()
        ->and($invalidated[0]->files[0]['disk'])->toBe('change-events-disk');

    // Nothing left to invalidate
    $this->events = [];
    app(AssetTransformers::class)->invalidate($asset);
    expect(($this->of)(AssetTransformsInvalidated::class))->toHaveCount(0);
});

it('reports a renamed folder’s old URL prefixes, including its transforms’', function () {
    // Disks put characters such as parentheses in URLs as they are, and only spaces get encoded
    $this->folderModel->update(['name' => 'My (2026)', 'path' => 'My (2026)/']);
    $this->disk->makeDirectory('My (2026)');
    $asset = ($this->image)();
    // The transform URL as Craft served it before the rename
    $transformUrl = strtok(app(AssetTransformers::class)->withImmediateTransforms(fn () => $asset->getUrl(['width' => 100])), '?');

    app(Folders::class)->renameFolderById($this->folderModel->id, 'Trips');

    $renamed = ($this->of)(FolderRenamed::class);
    $prefixes = $renamed[0]->prefixes();
    expect($renamed)->toHaveCount(1)
        ->and($renamed[0]->oldPath)->toBe('My (2026)/')
        ->and($renamed[0]->folder->path)->toBe('Trips/')
        ->and($prefixes)->toHaveCount(2)
        ->and($prefixes[0])->toBe('https://cdn.example.test/media%20files/My%20%282026%29/')
        ->and($transformUrl)->toStartWith($prefixes[1])
        ->and($prefixes[1])->toEndWith('/My%20(2026)/');
});
