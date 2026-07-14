<?php

declare(strict_types=1);

use craft\base\Fs;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Policies\AssetPolicy;
use CraftCms\Cms\Asset\Policies\VolumeFolderPolicy;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Filesystem\Filesystems\Temp;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Gate;

const ASSET_POLICY_VOLUME_UID = '00000000-0000-0000-0000-000000000001';

beforeEach(function () {
    $this->policy = app(AssetPolicy::class);
    $this->folderPolicy = app(VolumeFolderPolicy::class);

    Edition::set(Edition::Pro);

    config()->set('filesystems.disks.asset-policy-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-policy-test'),
    ]);

    $this->volumeModel = VolumeModel::factory()->create([
        'uid' => ASSET_POLICY_VOLUME_UID,
        'name' => 'Test Volume',
        'handle' => 'testVolume',
        'fs' => 'disk:asset-policy-test',
    ]);

    $this->volume = new Volume([
        'id' => $this->volumeModel->id,
        'uid' => $this->volumeModel->uid,
        'name' => $this->volumeModel->name,
        'handle' => $this->volumeModel->handle,
    ]);

    $assetFolder = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volumeModel->id,
    ]);
    $this->peerAsset = AssetModel::factory()->createElement([
        'volumeId' => $this->volumeModel->id,
        'folderId' => $assetFolder->id,
        'uploaderId' => User::factory()->create()->id,
    ]);

    $folder = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volumeModel->id,
    ]);
    $this->folder = Folders::getFolderById($folder->id);
});

it('is registered with the gate', function () {
    $asset = createAssetTestAsset($this->volume);
    $user = createAssetTestUser([]);

    $result = Gate::forUser($user)->allows('view', $asset);

    expect($result)->toBeBool();
});

it('returns false for folder view', function () {
    $user = createAssetTestUser([assetPolicyPermission('viewAssets')]);
    $asset = createAssetTestAsset($this->volume, isFolder: true);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with view permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('viewAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for uploader without view permission', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for peer asset with peer permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('viewPeerAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('viewAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with save permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('saveAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for uploader without save permission', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for peer asset with peer save permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('savePeerAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer save permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('saveAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeFalse();
});

it('returns false for folder delete', function () {
    $user = createAssetTestUser([assetPolicyPermission('deleteAssets')]);
    $asset = createAssetTestAsset($this->volume, isFolder: true);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with delete permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('deleteAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for uploader without delete permission', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for peer asset with peer delete permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('deletePeerAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer delete permission', function () {
    $user = createAssetTestUser([assetPolicyPermission('deleteAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeFalse();
});

it('returns same result for copy as view', function () {
    $user = createAssetTestUser([assetPolicyPermission('viewAssets')]);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $viewResult = $this->policy->view($user, $asset);
    $copyResult = $this->policy->copy($user, $asset);

    expect($copyResult)->toBe($viewResult);
});

it('allows view for temp upload filesystem regardless of permissions', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAssetWithTempFs($this->volume, uploaderId: $user->id);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeTrue();
});

it('allows delete for temp upload filesystem regardless of permissions', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAssetWithTempFs($this->volume, uploaderId: $user->id);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeTrue();
});

it('still checks peer permissions for view even on temp fs when uploader differs', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAssetWithTempFs($this->volume, uploaderId: 999);

    // Temp FS view bypass only applies when the user is the uploader (the code checks uploaderId first)
    // When uploaderId !== user.id, it goes to the peer permission check
    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('allows delete on temp fs even for peer assets', function () {
    $user = createAssetTestUser([]);
    $asset = createAssetTestAssetWithTempFs($this->volume, uploaderId: 999);

    // Delete checks temp FS before checking uploader, so it should allow
    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeTrue();
});

it('requires view and peer view permissions for viewing peer asset files', function () {
    expect($this->policy->viewFile(User::factory()->withPermissions([assetPolicyPermission('viewAssets')])->create(), $this->peerAsset))->toBeFalse()
        ->and($this->policy->viewFile(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('viewPeerAssets'),
        ])->create(), $this->peerAsset))->toBeTrue();
});

it('requires replace and peer replace permissions for replacing peer files', function () {
    expect($this->policy->replaceFile(User::factory()->withPermissions([
        assetPolicyPermission('viewAssets'),
        assetPolicyPermission('replaceFiles'),
    ])->create(), $this->peerAsset))->toBeFalse()
        ->and($this->policy->replaceFile(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('replaceFiles'),
            assetPolicyPermission('viewPeerAssets'),
            assetPolicyPermission('replacePeerFiles'),
        ])->create(), $this->peerAsset))->toBeTrue();
});

it('requires edit image and peer edit image permissions for editing peer images', function () {
    expect($this->policy->editImage(User::factory()->withPermissions([
        assetPolicyPermission('viewAssets'),
        assetPolicyPermission('editImages'),
    ])->create(), $this->peerAsset))->toBeFalse()
        ->and($this->policy->editImage(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('editImages'),
            assetPolicyPermission('viewPeerAssets'),
            assetPolicyPermission('editPeerImages'),
        ])->create(), $this->peerAsset))->toBeTrue();
});

it('requires target folder save plus source asset delete and peer permissions for moving peer files', function () {
    expect($this->policy->moveFile(User::factory()->withPermissions([
        assetPolicyPermission('viewAssets'),
        assetPolicyPermission('saveAssets'),
        assetPolicyPermission('deleteAssets'),
        assetPolicyPermission('viewPeerAssets'),
        assetPolicyPermission('savePeerAssets'),
    ])->create(), $this->peerAsset, $this->folder))->toBeFalse()
        ->and($this->policy->moveFile(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('saveAssets'),
            assetPolicyPermission('deleteAssets'),
            assetPolicyPermission('viewPeerAssets'),
            assetPolicyPermission('savePeerAssets'),
            assetPolicyPermission('deletePeerAssets'),
        ])->create(), $this->peerAsset, $this->folder))->toBeTrue();
});

it('requires all move-into folder permissions', function () {
    expect($this->folderPolicy->moveIntoFolder(User::factory()->withPermissions([
        assetPolicyPermission('viewAssets'),
        assetPolicyPermission('saveAssets'),
        assetPolicyPermission('deleteAssets'),
        assetPolicyPermission('viewPeerAssets'),
        assetPolicyPermission('savePeerAssets'),
    ])->create(), $this->folder))->toBeFalse()
        ->and($this->folderPolicy->moveIntoFolder(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('saveAssets'),
            assetPolicyPermission('deleteAssets'),
            assetPolicyPermission('viewPeerAssets'),
            assetPolicyPermission('savePeerAssets'),
            assetPolicyPermission('deletePeerAssets'),
        ])->create(), $this->folder))->toBeTrue();
});

it('requires view assets permission for viewing folder contents', function () {
    expect(Gate::forUser(User::factory()->withPermissions([])->create())->allows('viewContents', $this->folder))->toBeFalse()
        ->and(Gate::forUser(User::factory()->withPermissions([assetPolicyPermission('viewAssets')])->create())->allows('viewContents', $this->folder))->toBeTrue();
});

it('requires delete and create folder permissions for renaming folders', function () {
    expect($this->folderPolicy->renameFolder(User::factory()->withPermissions([
        assetPolicyPermission('viewAssets'),
        assetPolicyPermission('createFolders'),
    ])->create(), $this->folder))->toBeFalse()
        ->and($this->folderPolicy->renameFolder(User::factory()->withPermissions([
            assetPolicyPermission('viewAssets'),
            assetPolicyPermission('createFolders'),
            assetPolicyPermission('deleteAssets'),
        ])->create(), $this->folder))->toBeTrue();
});

// Helper functions
function assetPolicyPermission(string $permissionName): string
{
    return "$permissionName:".ASSET_POLICY_VOLUME_UID;
}

function createAssetTestUser(array $permissions): User
{
    static $nextUserId = 10000;

    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }
    };

    $user->id = ++$nextUserId;
    $user->grantedPermissions = $permissions;

    return $user;
}

function createAssetTestAsset(
    Volume $volume,
    ?int $uploaderId = null,
    bool $isFolder = false,
): Asset {
    $mockVolume = new class extends Volume
    {
        public function getFs(): FsInterface
        {
            return new Local;
        }
    };

    $mockVolume->id = $volume->id;
    $mockVolume->uid = $volume->uid;
    $mockVolume->name = $volume->name;
    $mockVolume->handle = $volume->handle;

    $asset = new class extends Asset
    {
        public ?Volume $mockVolume = null;

        public function getVolume(): Volume
        {
            return $this->mockVolume;
        }
    };

    $asset->id = 100;
    $asset->siteId = null;
    $asset->volumeId = $volume->id;
    $asset->uploaderId = $uploaderId;
    $asset->isFolder = $isFolder;
    $asset->mockVolume = $mockVolume;

    return $asset;
}

function createAssetTestAssetWithTempFs(
    Volume $volume,
    ?int $uploaderId = null,
): Asset {
    $tempFs = new Temp([
        'handle' => 'tempFs',
        'path' => sys_get_temp_dir().'/craft-policy-test-temp',
    ]);

    $mockVolume = new class extends Volume
    {
        public ?Temp $mockFs = null;

        public function getFs(): Temp
        {
            return $this->mockFs;
        }
    };

    $mockVolume->id = $volume->id;
    $mockVolume->uid = $volume->uid;
    $mockVolume->name = $volume->name;
    $mockVolume->handle = $volume->handle;
    $mockVolume->mockFs = $tempFs;
    $mockVolume->markAsTemporary();

    $asset = new class extends Asset
    {
        public ?Volume $mockVolume = null;

        public function getVolume(): Volume
        {
            return $this->mockVolume;
        }
    };

    $asset->id = 100;
    $asset->siteId = null;
    $asset->volumeId = $volume->id;
    $asset->uploaderId = $uploaderId;
    $asset->isFolder = false;
    $asset->mockVolume = $mockVolume;

    return $asset;
}
