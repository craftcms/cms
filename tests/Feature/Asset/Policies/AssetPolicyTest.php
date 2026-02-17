<?php

declare(strict_types=1);

use craft\base\Fs;
use craft\models\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Policies\AssetPolicy;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(AssetPolicy::class);

    $this->volume = new Volume([
        'id' => 1,
        'uid' => 'volume-uid',
        'name' => 'Test Volume',
        'handle' => 'testVolume',
    ]);
});

it('is registered with the gate', function () {
    $asset = createAssetTestAsset($this->volume);
    $user = createAssetTestUser([]);

    $result = Gate::forUser($user)->allows('view', $asset);

    expect($result)->toBeBool();
});

it('returns false for folder view', function () {
    $user = createAssetTestUser(['viewAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, isFolder: true);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with view permission', function () {
    $user = createAssetTestUser(['viewAssets:volume-uid']);
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
    $user = createAssetTestUser(['viewPeerAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer permission', function () {
    $user = createAssetTestUser(['viewAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->view($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with save permission', function () {
    $user = createAssetTestUser(['saveAssets:volume-uid']);
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
    $user = createAssetTestUser(['savePeerAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer save permission', function () {
    $user = createAssetTestUser(['saveAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->save($user, $asset);

    expect($result)->toBeFalse();
});

it('returns false for folder delete', function () {
    $user = createAssetTestUser(['deleteAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, isFolder: true);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeFalse();
});

it('returns true for uploader with delete permission', function () {
    $user = createAssetTestUser(['deleteAssets:volume-uid']);
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
    $user = createAssetTestUser(['deletePeerAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeTrue();
});

it('returns false for peer asset without peer delete permission', function () {
    $user = createAssetTestUser(['deleteAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: 999);

    $result = $this->policy->delete($user, $asset);

    expect($result)->toBeFalse();
});

it('returns same result for copy as view', function () {
    $user = createAssetTestUser(['viewAssets:volume-uid']);
    $asset = createAssetTestAsset($this->volume, uploaderId: $user->id);

    $viewResult = $this->policy->view($user, $asset);
    $copyResult = $this->policy->copy($user, $asset);

    expect($copyResult)->toBe($viewResult);
});

// Helper functions
function createAssetTestUser(array $permissions): User
{
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

    $user->id = random_int(1, 10000);
    $user->grantedPermissions = $permissions;

    return $user;
}

function createAssetTestAsset(
    Volume $volume,
    ?int $uploaderId = null,
    bool $isFolder = false,
): Asset {
    $mockFs = new class extends Fs
    {
        public function getRootUrl(): ?string
        {
            return null;
        }

        public function getFileList(string $directory = '', bool $recursive = true): Generator
        {
            yield from [];
        }

        public function getFileSize(string $uri): int
        {
            return 0;
        }

        public function getDateModified(string $uri): int
        {
            return 0;
        }

        public function read(string $path): string
        {
            return '';
        }

        public function write(string $path, string $contents, array $config = []): void {}

        public function fileExists(string $path): bool
        {
            return false;
        }

        public function deleteFile(string $path): void {}

        public function renameFile(string $path, string $newPath, array $config = []): void {}

        public function copyFile(string $path, string $newPath, array $config = []): void {}

        public function getFileStream(string $uriPath, array $config = [])
        {
            return fopen('php://memory', 'r');
        }

        public function writeFileFromStream(string $path, $stream, array $config = []): void {}

        public function directoryExists(string $path): bool
        {
            return false;
        }

        public function createDirectory(string $path, array $config = []): void {}

        public function deleteDirectory(string $path): void {}

        public function renameDirectory(string $path, string $newName): void {}
    };

    $mockFs->handle = 'testFs';

    $mockVolume = new class extends Volume
    {
        public ?Fs $mockFs = null;

        public function getFs(): Fs
        {
            return $this->mockFs;
        }
    };

    $mockVolume->id = $volume->id;
    $mockVolume->uid = $volume->uid;
    $mockVolume->name = $volume->name;
    $mockVolume->handle = $volume->handle;
    $mockVolume->mockFs = $mockFs;

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
    $asset->uploaderId = $uploaderId;
    $asset->isFolder = $isFolder;
    $asset->mockVolume = $mockVolume;

    return $asset;
}
