<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\GarbageCollection\Actions\RemoveStaleImportData;
use CraftCms\Cms\Import\Jobs\Import;
use Illuminate\Support\Facades\Storage;

it('removes import data that runs left behind and keeps data that’s still in use', function () {
    Storage::fake(Cms::config()->getImportDisk());
    $disk = Storage::disk(Cms::config()->getImportDisk());
    $stalePath = Import::spoolPath('stale-run', 'step').'/0.json';
    $activePath = Import::spoolPath('active-run', 'step').'/0.json';
    $disk->put($stalePath, '[]');
    $disk->put($activePath, '[]');
    touch($disk->path($stalePath), now()->subDays(8)->getTimestamp());
    touch($disk->path($activePath), now()->subDays(6)->getTimestamp());

    app(RemoveStaleImportData::class)();

    $disk->assertMissing($stalePath);
    $disk->assertExists($activePath);
});
