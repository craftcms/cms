<?php

declare(strict_types=1);

use craft\base\Fs;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

uses(TestCase::class);

beforeEach(function() {
    config()->set('filesystems.disks.volume-mixin', ['driver' => 'local']);
    $this->disk = Storage::fake('volume-mixin');
    $this->volume = new Volume(['fs' => 'volume-mixin']);
});

it('writes files and directories through the legacy volume methods', function() {
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, 'streamed');
    rewind($stream);

    $this->volume->write('file.txt', 'contents');
    $this->volume->write('hidden.txt', 'secret', [Fs::CONFIG_VISIBILITY => Fs::VISIBILITY_HIDDEN]);
    $this->volume->writeFileFromStream('stream.txt', $stream);
    $this->volume->createDirectory('folder');

    expect($this->disk->get('file.txt'))->toBe('contents')
        ->and($this->disk->getVisibility('file.txt'))->toBe('public')
        ->and($this->disk->getVisibility('hidden.txt'))->toBe('private')
        ->and($this->disk->get('stream.txt'))->toBe('streamed')
        ->and($this->disk->directoryExists('folder'))->toBeTrue();
});

it('throws a not found exception when reading a missing file from a throwing disk', function() {
    Storage::fake('volume-mixin', ['throw' => true]);

    $this->volume->read('missing.txt');
})->throws(FsObjectNotFoundException::class);

it('renames nested directories', function() {
    $this->disk->put('old/nested/file.txt', 'contents');

    $this->volume->renameDirectory('old', 'new');

    expect($this->disk->get('new/nested/file.txt'))->toBe('contents')
        ->and($this->disk->directoryExists('old'))->toBeFalse();
});
