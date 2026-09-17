<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\GarbageCollection\Actions\RemoveEmptyTempFolders;
use Illuminate\Support\Facades\DB;

it('removes empty temp folders', function () {
    $parentId = DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'parent',
        'path' => 'parent',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    DB::table(Table::VOLUMEFOLDERS)->insert([
        'name' => 'foo',
        'parentId' => $parentId,
        'path' => 'foo',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(2);

    app(RemoveEmptyTempFolders::class)();

    expect(DB::table(Table::VOLUMEFOLDERS))->count()->toBe(1);
});

it('preserves temporary folders targeted by active asset uploads', function () {
    $parentId = DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'parent',
        'path' => 'parent',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);
    $folderId = DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'upload',
        'parentId' => $parentId,
        'path' => 'upload',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);
    UploadSession::create([
        'id' => fake()->uuid(),
        'owner' => 'user:1',
        'handler' => AssetUploads::class,
        'uploader' => 'tus',
        'disk' => 'disk:uploads',
        'filename' => 'example.txt',
        'size' => 3,
        'parameters' => ['operation' => 'upload', 'folderId' => $folderId],
        'state' => [],
        'expiresAt' => now()->addHour(),
    ]);
    $expiredFolderId = DB::table(Table::VOLUMEFOLDERS)->insertGetId([
        'name' => 'expired upload',
        'parentId' => $parentId,
        'path' => 'expired-upload',
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);
    UploadSession::create([
        'id' => fake()->uuid(),
        'owner' => 'user:1',
        'handler' => AssetUploads::class,
        'uploader' => 'tus',
        'disk' => 'disk:uploads',
        'filename' => 'expired.txt',
        'size' => 3,
        'parameters' => ['operation' => 'upload', 'folderId' => $expiredFolderId],
        'state' => [],
        'expiresAt' => now()->subHour(),
    ]);

    app(RemoveEmptyTempFolders::class)();

    expect(DB::table(Table::VOLUMEFOLDERS)->where('id', $folderId)->exists())->toBeTrue()
        ->and(DB::table(Table::VOLUMEFOLDERS)->where('id', $expiredFolderId)->exists())->toBeFalse();
});
