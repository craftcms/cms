<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\GarbageCollection\Actions\RemoveExpiredUploads;
use Illuminate\Support\Facades\Storage;

function createUploadSessionForGarbageCollection(DateTimeInterface $expiresAt, string $uploader = 'tus'): UploadSession
{
    $session = UploadSession::create([
        'id' => fake()->uuid(),
        'owner' => 'user:1',
        'handler' => AssetUploads::class,
        'uploader' => $uploader,
        'disk' => 'uploads',
        'filename' => 'example.txt',
        'size' => 3,
        'parameters' => [],
        'state' => [],
        'expiresAt' => $expiresAt,
    ]);

    Storage::disk('uploads')->put($session->prefix().'/parts/0', 'abc');

    return $session;
}

it('removes expired upload sessions and their temporary files', function () {
    Storage::fake('uploads');
    $expired = createUploadSessionForGarbageCollection(now()->subSecond());
    $active = createUploadSessionForGarbageCollection(now()->addHour());

    app(RemoveExpiredUploads::class)();

    expect(UploadSession::query()->pluck('id')->all())->toBe([$active->id]);
    Storage::disk('uploads')->assertMissing($expired->prefix().'/parts/0');
    Storage::disk('uploads')->assertExists($active->prefix().'/parts/0');
});

it('fails when an expired upload session cannot be cleaned up', function () {
    Storage::fake('uploads');
    $session = createUploadSessionForGarbageCollection(now()->subSecond(), uploader: 'missing');

    expect(fn () => app(RemoveExpiredUploads::class)())
        ->toThrow(RuntimeException::class, 'Some upload sessions could not be cleaned up.');

    expect(UploadSession::query()->whereKey($session->id)->exists())->toBeTrue();
});
