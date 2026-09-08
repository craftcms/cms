<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploaders;
use CraftCms\Cms\Filesystem\Uploaders\ChunkedUploader;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    foreach (['upload-first', 'upload-second'] as $name) {
        config()->set("filesystems.disks.$name", ['driver' => 'local', 'root' => storage_path("framework/testing/$name")]);
        Storage::fake($name);
    }

    Cms::config()->tempAssetUploadFs = 'disk:upload-first';
});

it('routes custom uploader operations to each sessions disk', function () {
    $manager = app(Uploaders::class);
    $manager->extend('custom', fn (Container $app) => $app->make(ChunkedUploader::class));
    Cms::config()->uploader = 'custom';

    expect($manager->getDefaultDriver())->toBe('custom');

    $uploader = $manager->driver();
    $sessions = [];

    foreach (['upload-first', 'upload-second'] as $disk) {
        $session = new UploadSession(['id' => $disk, 'disk' => "disk:$disk", 'size' => 3, 'chunkSize' => 3]);
        $sessions[] = $session;
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'abc');
        rewind($stream);

        try {
            $uploader->receive($session, 1, $stream);
        } finally {
            fclose($stream);
        }
    }

    Storage::disk('upload-first')->assertExists('upload-sessions/upload-first/parts/1');
    Storage::disk('upload-first')->assertMissing('upload-sessions/upload-second/parts/1');
    Storage::disk('upload-second')->assertExists('upload-sessions/upload-second/parts/1');

    $uploader->abort($sessions[0]);
    Storage::disk('upload-first')->assertMissing('upload-sessions/upload-first/parts/1');
    Storage::disk('upload-second')->assertExists('upload-sessions/upload-second/parts/1');
});
