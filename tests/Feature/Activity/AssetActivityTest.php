<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\AssetFileReplaced;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

it('records safe asset file replacement facts', function () {
    actingAs(User::findOne());

    $root = storage_path('framework/testing/activity-assets');
    File::ensureDirectoryExists($root);
    File::cleanDirectory($root);
    config()->set('filesystems.disks.activity-assets', ['driver' => 'local', 'root' => $root]);

    $volume = Volume::factory()->create([
        'name' => 'Activity assets',
        'handle' => 'activityAssets',
        'fs' => 'disk:activity-assets',
    ]);
    $folder = app(Folders::class)->getRootFolderByVolumeId($volume->id);
    $original = Path::temp('original.txt');
    File::put($original, 'old');

    $asset = Elements::createElement([
        'type' => Asset::class,
        'volumeId' => $volume->id,
        'newFolderId' => $folder->id,
        'tempFilePath' => $original,
        'newFilename' => 'original.txt',
    ]);
    $asset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);
    expect(Elements::saveElement($asset))->toBeTrue();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $replacement = Path::temp('replacement.txt');
    File::put($replacement, 'replacement');
    app(Assets::class)->replaceAssetFile($asset, $replacement, 'replacement.txt', 'text/plain');

    $event = app(Activities::class)->query()->subject(ActivitySubject::fromElement($asset))->firstOrFail();

    expect($event->eventType)->toBe(AssetFileReplaced::class)
        ->and($event->siteId)->toBe($asset->siteId)
        ->and($event->data)->toEqual([
            'oldFilename' => 'original.txt',
            'newFilename' => 'replacement.txt',
            'oldMimeType' => 'text/plain',
            'newMimeType' => 'text/plain',
            'oldSize' => 3,
            'newSize' => 11,
        ])
        ->and(app(Activities::class)->format($event))
        ->toBe('Replaced original.txt with replacement.txt.');
});
