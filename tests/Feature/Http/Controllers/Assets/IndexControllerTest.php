<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/index-controller-test/test-disk'),
    ]);
});

it('requires authentication', function () {
    auth()->logout();

    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets")
        ->assertRedirect();
});

it('renders the assets index page', function () {
    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets")
        ->assertOk();
});

it('sets the CP-relative path for legacy URL generation', function () {
    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets")
        ->assertOk()
        ->assertSee('"path":"assets"', false)
        ->assertDontSee(sprintf('"path":"%s/assets"', $cpTrigger), false);
});

it('renders with a default source', function () {
    $volume = Volume::factory()->create([
        'fs' => 'disk:test-disk',
        'handle' => 'testvolume',
    ]);

    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets", ['defaultSource' => $volume->handle])->assertOk();
});

it('includes a volume’s subfolders in the index results', function () {
    $volumeModel = Volume::factory()->create([
        'fs' => 'disk:test-disk',
        'handle' => 'testvolume',
    ]);

    $volume = Volumes::getVolumeById($volumeModel->id);
    Folders::ensureFolderByFullPathAndVolume('a-subfolder', $volume);

    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets/{$volumeModel->handle}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('assets/Index')
            ->where('pagination.total', 1)
            ->has('data', 1)
            // The folder row is flagged and carries the URL to navigate into it.
            ->where('data.0.isFolder', true)
            ->where('data.0.folderUrl', fn (string $url) => str_contains($url, "assets/{$volumeModel->handle}/a-subfolder"))
        );
});

it('scopes the results to the subfolder named in the path', function () {
    $volumeModel = Volume::factory()->create([
        'fs' => 'disk:test-disk',
        'handle' => 'testvolume',
    ]);

    $volume = Volumes::getVolumeById($volumeModel->id);
    // parent/ holds child/, so browsing parent/ should list child/ — not the
    // volume root's folders.
    Folders::ensureFolderByFullPathAndVolume('parent/child', $volume);

    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets/{$volumeModel->handle}/parent")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pagination.total', 1)
            ->has('data', 1)
            ->where('data.0.isFolder', true)
            ->where('data.0.folderUrl', fn (string $url) => str_contains($url, "assets/{$volumeModel->handle}/parent/child"))
        );
});

it('passes the route path segment through as defaultSource', function () {
    $volume = Volume::factory()->create([
        'fs' => 'disk:test-disk',
        'handle' => 'testvolume',
    ]);

    $cpTrigger = Cms::config()->cpTrigger;

    get("/{$cpTrigger}/assets/{$volume->handle}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('assets/Index')
            // The raw path echoes back so client reloads keep the folder in the
            // URL; the resolved source key drives which source is active.
            ->where('defaultSource', $volume->handle)
            ->where('source.key', "volume:{$volume->uid}")
        );
});
