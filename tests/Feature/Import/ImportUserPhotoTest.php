<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Import\UserImporter;
use CraftCms\UrlValidator\UrlValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->diskRoot = storage_path('framework/testing/import-user-photo/'.bin2hex(random_bytes(4)));
    config()->set('filesystems.disks.import-user-photo-disk', [
        'driver' => 'local',
        'root' => $this->diskRoot,
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'import-user-photo-disk']);
    ProjectConfig::set('users.photoVolumeUid', $this->volume->uid);

    $this->sourceDir = Path::temp('import-user-photo-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($this->sourceDir);

    $this->imageFile = function (string $filename): string {
        $path = "$this->sourceDir/$filename";
        file_put_contents($path, UploadedFile::fake()->image('image.png', 10, 10)->getContent());

        return $path;
    };

    $this->importer = UserImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->remoteImporter = function (): UserImporter {
        $importer = new class extends UserImporter
        {
            public static function urlValidator(?callable $resolver = null): UrlValidator
            {
                return parent::urlValidator(fn () => ['93.184.216.34']);
            }
        };

        return $importer->site(Sites::getPrimarySite()->handle)->transformer(null);
    };

    $this->importUser = fn (mixed $photo, ?UserImporter $importer = null) => $this->import->importItem($importer ?? $this->importer, [
        'username' => 'importeduser',
        'email' => 'imported@example.com',
        'photoId' => $photo,
        'matchCriteria' => ['email' => 'email'],
    ]);

    $this->user = fn () => UserElement::find()->username('importeduser')->status(null)->one();
});

afterEach(function () {
    File::deleteDirectory($this->sourceDir);
    File::deleteDirectory($this->diskRoot);
});

it('imports a photo from a local file', function () {
    $source = ($this->imageFile)('avatar.png');

    ($this->importUser)($source);

    $photo = ($this->user)()->getPhoto();

    expect($photo)->not->toBeNull()
        ->and($photo->getFilename())->toBe('avatar.png')
        ->and($photo->volumeId)->toBe($this->volume->id)
        ->and(file_exists($source))->toBeTrue();
});

it('imports a photo from a URL', function () {
    Http::fake(['example.com/*' => Http::response(UploadedFile::fake()->image('image.png', 10, 10)->getContent(), 200, ['Content-Type' => 'image/png'])]);

    ($this->importUser)('https://example.com/photos/avatar.png?v=2', ($this->remoteImporter)());

    expect(($this->user)()->getPhoto()?->getFilename())->toBe('avatar.png');
});

it('takes the photo extension from the content type for a URL without one', function () {
    Http::fake(['example.com/*' => Http::response(UploadedFile::fake()->image('image.png', 10, 10)->getContent(), 200, ['Content-Type' => 'image/png'])]);

    ($this->importUser)('https://example.com/photos/avatar', ($this->remoteImporter)());

    expect(($this->user)()->getPhoto()?->getFilename())->toBe('avatar.png');
});

it('still imports a photo by asset ID', function () {
    ($this->importUser)(($this->imageFile)('avatar.png'));
    $photoId = ($this->user)()->photoId;

    $this->import->importItem($this->importer, [
        'username' => 'otheruser',
        'email' => 'other@example.com',
        'photoId' => (string) $photoId,
        'matchCriteria' => ['email' => 'email'],
    ]);

    expect(UserElement::find()->username('otheruser')->status(null)->one()->photoId)->toBe($photoId);
});

it('doesn’t create another asset when the same photo is imported again', function () {
    $source = ($this->imageFile)('avatar.png');

    ($this->importUser)($source);
    $photoId = ($this->user)()->photoId;

    ($this->importUser)($source);

    expect(($this->user)()->photoId)->toBe($photoId)
        ->and(Asset::find()->volumeId($this->volume->id)->count())->toBe(1);
});

it('logs a warning and still imports the user when the photo isn’t an image', function () {
    ImportLog::spy();

    $source = "$this->sourceDir/notes.txt";
    file_put_contents($source, 'not an image');

    ($this->importUser)($source);

    $user = ($this->user)();

    expect($user)->not->toBeNull()
        ->and($user->photoId)->toBeNull();
    ImportLog::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'notes.txt'));
});
