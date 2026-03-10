<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Support\Facades\Folders as FoldersFacade;
use Illuminate\Database\Query\Builder;

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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Cached Folder',
    ]);

    $first = $this->folders->getFolderById($model->id);
    $second = $this->folders->getFolderById($model->id);

    expect($first)->toBe($second);
});

it('can get a folder by uid', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);

    app()->forgetInstance(Folders::class);
    $this->folders = app(Folders::class);

    $rootFolder = $this->folders->getRootFolderByVolumeId($volume->id);

    expect($rootFolder)->toBeInstanceOf(VolumeFolder::class);
    expect($rootFolder->volumeId)->toBe($volume->id);
    expect($rootFolder->parentId)->toBeNull();
    expect($rootFolder->path)->toBe('');
});

it('creates root folder if it does not exist', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);

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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    VolumeFolderModel::factory()->count(3)->create(['volumeId' => $volume->id]);

    expect($this->folders->getTotalFolders(['volumeId' => $volume->id]))->toBe(3);
});

it('can check if folders exist', function () {
    expect($this->folders->foldersExist(['name' => 'nonexistent']))->toBeFalse();

    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    VolumeFolderModel::factory()->create(['volumeId' => $volume->id, 'name' => 'exists']);

    expect($this->folders->foldersExist(['volumeId' => $volume->id]))->toBeTrue();
});

it('can get all descendant folders', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
});

it('can exclude parent from descendant folders', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);

    $folder = new VolumeFolder;
    $folder->volumeId = $volume->id;
    $folder->name = 'New Folder';
    $folder->path = 'new-folder/';

    $this->folders->storeFolderModel($folder);

    expect($folder->id)->not->toBeNull();
    expect($folder->uid)->not->toBeNull();

    $model = VolumeFolderModel::find($folder->id);
    expect($model->name)->toBe('New Folder');
    expect($model->path)->toBe('new-folder/');
});

it('can update an existing folder record', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $model = VolumeFolderModel::factory()->create([
        'volumeId' => $volume->id,
        'name' => 'Original',
        'path' => 'original/',
    ]);

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
});

it('can ensure folder by full path and volume', function () {
    $volumeModel = Volume::factory()->create(['fs' => 'disk:test-disk']);

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
    $volumeModel = Volume::factory()->create(['fs' => 'disk:test-disk']);

    app()->forgetInstance(Folders::class);
    app()->forgetInstance(Volumes::class);
    $this->folders = app(Folders::class);

    $volume = app(Volumes::class)->getVolumeById($volumeModel->id);

    $first = $this->folders->ensureFolderByFullPathAndVolume('foo/bar', $volume);
    $second = $this->folders->ensureFolderByFullPathAndVolume('foo/bar', $volume);

    expect($first->id)->toBe($second->id);
});

it('can apply :empty: criteria for null columns', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
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
