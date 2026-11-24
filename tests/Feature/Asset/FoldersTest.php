<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Folders as FoldersFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalFlysystemAdapter;

beforeEach(function () {
    $this->folders = app(Folders::class);

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/folders-test/test-disk'),
    ]);
});

it('is a singleton', function () {
    expect(FoldersFacade::getFacadeRoot())->toBe(app(Folders::class));
    expect($this->folders)->toBe(app(Folders::class));
});

it('can get a folder by id', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Test Folder',
        'path' => 'test-folder/',
    ]);

    $folder = $this->folders->getFolderById($model->id);

    expect($folder)->toBeInstanceOf(VolumeFolder::class);
    expect($folder->name)->toBe('Test Folder');
    expect($folder->path)->toBe('test-folder/');
});

it('returns null for non-existent folder id', function () {
    expect($this->folders->getFolderById(999))->toBeNull();
});

it('caches folder by id lookups', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Cached Folder',
    ]);

    $first = $this->folders->getFolderById($model->id);
    $second = $this->folders->getFolderById($model->id);

    expect($first)->toBe($second);
    expect($this->folders->getFolderByUid($model->uid))->toBe($first);
    $root = $this->folders->getRootFolderByVolumeId($volume->id);
    expect($this->folders->getFolderById($model->id))->toBe($root);

    $fresh = $this->folders->findFolder(['id' => $model->id]);
    expect($fresh)->not->toBe($first)
        ->and($this->folders->getFolderById($model->id))->toBe($fresh)
        ->and($this->folders->getFolderByUid($model->uid))->toBe($fresh)
        ->and($this->folders->getRootFolderByVolumeId($volume->id))->toBe($fresh);
});

it('can get a folder by uid', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'UID Folder',
    ]);

    $folder = $this->folders->getFolderByUid($model->uid);

    expect($folder)->toBeInstanceOf(VolumeFolder::class);
    expect($folder->name)->toBe('UID Folder');
});

it('returns null for non-existent folder uid', function () {
    expect($this->folders->getFolderByUid('non-existent-uid'))->toBeNull();
});

it('can find folders by criteria', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Folder A',
        'path' => 'folder-a/',
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Folder B',
        'path' => 'folder-b/',
    ]);

    $folders = $this->folders->findFolders(['volumeId' => $volume->id]);

    expect($folders)->toHaveCount(2);
    expect($folders->pluck('name')->sort()->values()->all())->toBe(['Folder A', 'Folder B']);
});

it('can find folders with string order criteria', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Bravo',
        'path' => 'bravo/',
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Alpha',
        'path' => 'alpha/',
    ]);

    $folders = $this->folders->findFolders([
        'volumeId' => $volume->id,
        'order' => 'name asc',
    ]);

    expect($folders->first()->name)->toBe('Alpha');
});

it('can find folders with descending order', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Alpha',
        'path' => 'alpha/',
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Bravo',
        'path' => 'bravo/',
    ]);

    $folders = $this->folders->findFolders([
        'volumeId' => $volume->id,
        'order' => 'name desc',
    ]);

    expect($folders->first()->name)->toBe('Bravo');
});

it('can find folders with array order criteria', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Bravo',
        'path' => 'bravo/',
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Alpha',
        'path' => 'alpha/',
    ]);

    $folders = $this->folders->findFolders([
        'volumeId' => $volume->id,
        'order' => ['name' => SORT_ASC],
    ]);

    expect($folders->first()->name)->toBe('Alpha');
});

it('can find a single folder', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Single',
    ]);

    $folder = $this->folders->findFolder(['volumeId' => $volume->id, 'name' => 'Single']);

    expect($folder)->toBeInstanceOf(VolumeFolder::class);
    expect($folder->id)->toBe($model->id);
});

it('returns null when findFolder matches nothing', function () {
    expect($this->folders->findFolder(['name' => 'nonexistent']))->toBeNull();
});

it('can find folder by path containing a comma', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'my,folder',
        'path' => 'my,folder/',
    ]);

    $folder = $this->folders->findFolder([
        'volumeId' => $volume->id,
        'path' => 'my,folder/',
    ]);

    expect($folder)->not->toBeNull();
    expect($folder->name)->toBe('my,folder');
});

it('can get root folder by volume id', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);

    app()->forgetInstance(Folders::class);
    $this->folders = app(Folders::class);

    $rootFolder = $this->folders->getRootFolderByVolumeId($volume->id);

    expect($rootFolder)->toBeInstanceOf(VolumeFolder::class);
    expect($rootFolder->volumeId)->toBe($volume->id);
    expect($rootFolder->parentId)->toBeNull();
    expect($rootFolder->path)->toBe('');
});

it('creates root folder if it does not exist', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);

    expect(VolumeFolderModel::where('volumeId', $volume->id)->count())->toBe(0);

    app()->forgetInstance(Folders::class);
    $this->folders = app(Folders::class);

    $rootFolder = $this->folders->getRootFolderByVolumeId($volume->id);

    expect($rootFolder)->not->toBeNull();
    expect(VolumeFolderModel::where('volumeId', $volume->id)->count())->toBe(1);
});

it('returns null for root folder of non-existent volume', function () {
    expect($this->folders->getRootFolderByVolumeId(999))->toBeNull();
});

it('can get total folders', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->count(3)->create(['volumeId' => $volume->id]);

    expect($this->folders->getTotalFolders(['volumeId' => $volume->id]))->toBe(3);
});

it('can check if folders exist', function () {
    expect($this->folders->foldersExist(['name' => 'nonexistent']))->toBeFalse();

    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    VolumeFolderModel::factory()->create(['volumeId' => $volume->id, 'name' => 'exists']);

    expect($this->folders->foldersExist(['volumeId' => $volume->id]))->toBeTrue();
});

it('can get all descendant folders', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Root',
        'path' => '',
        'parentId' => null,
    ]);
    $child = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Child',
        'path' => 'child/',
        'parentId' => $root->id,
    ]);
    $grandchild = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Grandchild',
        'path' => 'child/grandchild/',
        'parentId' => $child->id,
    ]);

    $rootData = new VolumeFolder([
        'id' => $root->id,
        'volumeId' => $volume->id,
        'name' => 'Root',
        'path' => '',
        'parentId' => null,
    ]);

    $descendants = $this->folders->getAllDescendantFolders($rootData);

    expect($descendants)->toHaveCount(2);
    expect(array_keys($descendants))->toContain($child->id, $grandchild->id);
});

it('can get descendant folders as tree', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Root',
        'path' => '',
        'parentId' => null,
    ]);
    $child = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Child',
        'path' => 'child/',
        'parentId' => $root->id,
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Grandchild',
        'path' => 'child/grandchild/',
        'parentId' => $child->id,
    ]);

    $rootData = new VolumeFolder([
        'id' => $root->id,
        'volumeId' => $volume->id,
        'name' => 'Root',
        'path' => '',
        'parentId' => null,
    ]);

    $tree = $this->folders->getAllDescendantFolders($rootData, asTree: true);

    expect($tree)->toHaveCount(1);
    $childNode = array_first($tree);
    expect($childNode->name)->toBe('Child');
    expect($childNode->getChildren())->toHaveCount(1);
    expect($childNode->getChildren()[0]->name)->toBe('Grandchild');
    expect($this->folders->getFolderById($child->id))->toBe($childNode);

    $fresh = $this->folders->findFolder(['id' => $child->id]);
    $fresh->setChildren([]);
    expect($fresh)->not->toBe($childNode)
        ->and($childNode->getChildren())->toHaveCount(1);
});

it('can exclude parent from descendant folders', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Root',
        'path' => '',
        'parentId' => null,
    ]);
    $child = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Child',
        'path' => 'child/',
        'parentId' => $root->id,
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Grandchild',
        'path' => 'child/grandchild/',
        'parentId' => $child->id,
    ]);

    $childData = new VolumeFolder([
        'id' => $child->id,
        'volumeId' => $volume->id,
        'name' => 'Child',
        'path' => 'child/',
        'parentId' => $root->id,
    ]);

    $withParent = $this->folders->getAllDescendantFolders($childData, withParent: true);
    $withoutParent = $this->folders->getAllDescendantFolders($childData, withParent: false);

    expect($withParent)->toHaveKey($child->id);
    expect($withoutParent)->not->toHaveKey($child->id);
    expect($withoutParent)->toHaveCount(count($withParent) - 1);
});

it('can store a new folder record', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);

    $folder = new VolumeFolder;
    $folder->volumeId = $volume->id;
    $folder->name = 'New Folder';
    $folder->path = 'new-folder/';

    $this->folders->storeFolderModel($folder);

    expect($folder->id)->not->toBeNull();
    expect($folder->uid)->not->toBeNull();
    expect($this->folders->getFolderById($folder->id)?->uid)->toBe($folder->uid);

    $model = VolumeFolderModel::find($folder->id);
    expect($model->name)->toBe('New Folder');
    expect($model->path)->toBe('new-folder/');
});

it('refreshes lookups after updating and deleting an existing folder record', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Original',
        'path' => 'original/',
    ]);

    $this->folders->getFolderById($model->id);
    $this->folders->getFolderByUid($model->uid);
    $this->folders->getRootFolderByVolumeId($volume->id);

    $folder = new VolumeFolder([
        'id' => $model->id,
        'volumeId' => $volume->id,
        'name' => 'Updated',
        'path' => 'updated/',
    ]);

    $this->folders->storeFolderModel($folder);

    $model->refresh();
    expect($model->name)->toBe('Updated');
    expect($model->path)->toBe('updated/');
    expect($this->folders->getFolderById($model->id)->name)->toBe('Updated')
        ->and($this->folders->getFolderByUid($model->uid)->name)->toBe('Updated')
        ->and($this->folders->getRootFolderByVolumeId($volume->id)->name)->toBe('Updated');

    $this->folders->deleteFoldersByIds($model->id, deleteDir: false);
    expect($this->folders->getFolderById($model->id))->toBeNull()
        ->and($this->folders->getFolderByUid($model->uid))->toBeNull()
        ->and($this->folders->getRootFolderByVolumeId($volume->id)->id)->not->toBe($model->id);
});

it('renames subfolders whose paths differ in case from the renamed folder', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = $this->folders->getRootFolderByVolumeId($volume->id);
    $disk = Storage::disk('test-disk');
    $disk->deleteDirectory('');

    // `uploads/moved/` lives inside `Uploads/`, as it would on case-insensitive storage, while
    // `uploads/separate/` is a separate directory on case-sensitive storage
    foreach (['Uploads/moved/nested', 'Uploads/photos/trip', 'Uploads/separate/inside', 'uploads/separate', 'Ⱥ/moved', 'Straße/moved'] as $directory) {
        $disk->makeDirectory($directory);
    }

    $store = function (int $parentId, string $path) use ($volume): int {
        $folder = new VolumeFolder([
            'name' => basename($path),
            'parentId' => $parentId,
            'volumeId' => $volume->id,
            'path' => $path,
        ]);
        $this->folders->storeFolderModel($folder);

        return $folder->id;
    };

    $folder = $store($root->id, 'Uploads/');
    $folderIds = [
        'moved' => $movedId = $store($folder, 'uploads/moved/'),
        'nested' => $store($movedId, 'uploads/moved/nested/'),
        'photos' => $photosId = $store($folder, 'Uploads/Photos/'),
        'trip' => $store($photosId, 'Uploads/photos/trip/'),
        'separate' => $separateId = $store($folder, 'uploads/separate/'),
        'inside' => $store($separateId, 'Uploads/separate/inside/'),
        'recordOnly' => $store($folder, 'Uploads/record-only/'),
        // `Ⱥ` and `ⱥ` differ in byte length, and `ß` and `SS` in character count
        'unicodeMoved' => $store($unicodeFolder = $store($root->id, 'Ⱥ/'), 'ⱥ/moved/'),
        'foldedMoved' => $store($foldedFolder = $store($root->id, 'Straße/'), 'STRASSE/moved/'),
    ];

    try {
        $this->folders->renameFolderById($folder, 'Renamed');
        $this->folders->renameFolderById($unicodeFolder, 'Unicode');
        $this->folders->renameFolderById($foldedFolder, 'Folded');

        $paths = VolumeFolderModel::whereIn('id', $folderIds)->pluck('path', 'id');

        expect(array_map(fn (int $id) => $paths[$id], $folderIds))->toBe([
            'moved' => 'Renamed/moved/',
            'nested' => 'Renamed/moved/nested/',
            'photos' => 'Renamed/Photos/',
            'trip' => 'Renamed/photos/trip/',
            // On case-insensitive storage, `uploads/separate` was inside `Uploads` and moved along with it
            'separate' => $disk->directoryExists('uploads/separate') ? 'uploads/separate/' : 'Renamed/separate/',
            'inside' => 'Renamed/separate/inside/',
            'recordOnly' => 'Renamed/record-only/',
            'unicodeMoved' => 'Unicode/moved/',
            'foldedMoved' => 'Folded/moved/',
        ]);
    } finally {
        $disk->deleteDirectory('');
    }
});

it('regenerates transforms of assets in a renamed folder and its subfolders on remote storage, whichever site it’s renamed from', function () {
    // Only Laravel’s local adapter gets its transforms’ `fileExists` checked against the disk
    Storage::extend('folders-test-remote', function ($app, array $config) {
        $adapter = new LocalFlysystemAdapter($config['root']);

        return new FilesystemAdapter(new Flysystem($adapter), $adapter, $config);
    });
    config()->set('filesystems.disks.remote-disk', [
        'driver' => 'folders-test-remote',
        'root' => storage_path('framework/testing/folders-test/remote-disk'),
        'url' => 'https://remote.test/media',
    ]);
    $disk = Storage::disk('remote-disk');
    $disk->deleteDirectory('');

    $volume = Volume::factory()->create([
        'disk' => 'remote-disk',
        'hasUrls' => true,
        'propagationMethod' => PropagationMethod::Custom->value,
    ]);
    $root = $this->folders->getRootFolderByVolumeId($volume->id);
    $folder = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $root->id,
        'name' => 'Été',
        'path' => 'Été/',
    ]);
    $createImage = function (string $subfolderPath) use ($volume, $folder, $disk): int {
        $subfolder = VolumeFolderModel::factory()->create([
            'volumeId' => $volume->id,
            'parentId' => $folder->id,
            'name' => basename($subfolderPath),
            'path' => $subfolderPath,
        ]);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $subfolder->id,
            'filename' => 'beach.jpg',
            'kind' => 'image',
            'width' => 1200,
            'height' => 800,
            'dateModified' => now()->subMinute(),
        ]);
        $disk->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));

        return $asset->id;
    };
    $assetIds = [$createImage('Été/Trips/'), $createImage('été/Beaches/')];
    $transformers = app(AssetTransformers::class);
    $transformPath = fn (int $assetId) => rawurldecode(Str::after(strtok($transformers->withImmediateTransforms(
        fn () => $transformers->transform(Asset::find()->id($assetId)->one(), ['width' => 100])->url,
    ), '?'), 'https://remote.test/media/'));

    try {
        $oldPaths = array_map($transformPath, $assetIds);
        expect($oldPaths[0])->toStartWith('_'.$volume->uid.'/Été/Trips/')
            ->and($oldPaths[1])->toStartWith('_'.$volume->uid.'/été/Beaches/');

        // On case-insensitive storage, `été/Beaches` is inside `Été`
        $disk->move('été/Beaches/beach.jpg', 'Été/Beaches/beach.jpg');
        $disk->deleteDirectory('été');

        // The assets only exist in the primary site
        $primarySite = Sites::getPrimarySite();
        Sites::setCurrentSite(Site::factory()->create()->id);
        $this->folders->renameFolderById($folder->id, 'Summer');
        Sites::setCurrentSite($primarySite);

        $newPaths = array_map($transformPath, $assetIds);

        expect($newPaths)->toBe([
            str_replace('/Été/', '/Summer/', $oldPaths[0]),
            str_replace('/été/', '/Summer/', $oldPaths[1]),
        ])
            ->and($disk->allFiles('_'.$volume->uid))->toEqualCanonicalizing($newPaths);
    } finally {
        $disk->deleteDirectory('');
    }
});

it('clears caches that depend on a renamed folder’s assets, including ones rebuilt during the rename', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = $this->folders->getRootFolderByVolumeId($volume->id);
    $folder = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $root->id,
        'name' => 'Photos',
        'path' => 'Photos/',
    ]);
    $subfolder = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $folder->id,
        'name' => 'Trips',
        'path' => 'Photos/Trips/',
    ]);
    $otherFolder = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $root->id,
        'name' => 'Other',
        'path' => 'Other/',
    ]);
    $assetIds = [
        AssetModel::factory()->create(['volumeId' => $volume->id, 'folderId' => $folder->id])->id,
        AssetModel::factory()->create(['volumeId' => $volume->id, 'folderId' => $subfolder->id])->id,
    ];
    $otherAssetId = AssetModel::factory()->create(['volumeId' => $volume->id, 'folderId' => $otherFolder->id])->id;
    Storage::disk('test-disk')->makeDirectory('Photos/Trips');

    // Caches the asset’s folder path the way a `{% cache %}` tag would
    $elementCaches = app(ElementCaches::class);
    $cache = function (int $assetId) use ($elementCaches): void {
        $elementCaches->startCollectingCacheInfo();
        $folderPath = Asset::find()->id($assetId)->status(null)->one()->folderPath;
        [$dependency] = $elementCaches->stopCollectingCacheInfo();
        DependencyCache::forever("asset-$assetId", $folderPath, $dependency);
    };
    array_map($cache, [...$assetIds, $otherAssetId]);

    // A request made while the folder records still have their old paths caches the first asset again
    $invalidatedIds = [];
    Event::listen(AssetTransformsInvalidating::class, function (AssetTransformsInvalidating $event) use (&$invalidatedIds, $cache) {
        $invalidatedIds[] = $event->asset->id;
        if (count($invalidatedIds) === 2) {
            $cache($invalidatedIds[0]);
        }
    });

    try {
        expect(DependencyCache::get("asset-{$assetIds[1]}"))->toBe('Photos/Trips/');

        $this->folders->renameFolderById($folder->id, 'Albums');

        expect($invalidatedIds)->toHaveCount(2)
            ->and(DependencyCache::get("asset-{$assetIds[0]}"))->toBeNull()
            ->and(DependencyCache::get("asset-{$assetIds[1]}"))->toBeNull()
            ->and(DependencyCache::get("asset-$otherAssetId"))->toBe('Other/');
    } finally {
        Storage::disk('test-disk')->deleteDirectory('');
    }
});

it('can ensure folder by full path and volume', function () {
    $volumeModel = Volume::factory()->create(['disk' => 'test-disk']);

    app()->forgetInstance(Folders::class);
    app()->forgetInstance(Volumes::class);
    $this->folders = app(Folders::class);

    $volume = app(Volumes::class)->getVolumeById($volumeModel->id);

    $folder = $this->folders->ensureFolderByFullPathAndVolume('foo/bar/baz', $volume);

    expect($folder)->toBeInstanceOf(VolumeFolder::class);
    expect($folder->name)->toBe('baz');
    expect($folder->path)->toBe('foo/bar/baz/');

    $allFolders = $this->folders->findFolders(['volumeId' => $volume->id]);
    // root + foo + bar + baz = 4
    expect($allFolders)->toHaveCount(4);
});

it('reuses existing folders in ensureFolderByFullPathAndVolume', function () {
    $volumeModel = Volume::factory()->create(['disk' => 'test-disk']);

    app()->forgetInstance(Folders::class);
    app()->forgetInstance(Volumes::class);
    $this->folders = app(Folders::class);

    $volume = app(Volumes::class)->getVolumeById($volumeModel->id);

    $first = $this->folders->ensureFolderByFullPathAndVolume('foo/bar', $volume);
    $second = $this->folders->ensureFolderByFullPathAndVolume('foo/bar', $volume);

    expect($first->id)->toBe($second->id);
});

it('can apply :empty: criteria for null columns', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $root = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Root',
        'parentId' => null,
    ]);
    VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Has Parent',
        'parentId' => $root->id,
    ]);

    $rootFolders = $this->folders->findFolders([
        'volumeId' => $volume->id,
        'parentId' => ':empty:',
    ]);

    expect($rootFolders)->toHaveCount(1);
    expect($rootFolders->first()->name)->toBe('Root');
});

it('resets caches', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Cached',
    ]);

    $this->folders->getFolderById($model->id);
    $this->folders->reset();

    // After reset, it should re-query (no error means cache was cleared)
    $folder = $this->folders->getFolderById($model->id);
    expect($folder->name)->toBe('Cached');
});

it('creates a folder query builder', function () {
    $query = $this->folders->createFolderQuery();

    expect($query)->toBeInstanceOf(Builder::class);
});
