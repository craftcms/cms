<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Events\ThumbUrlResolving;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\QueryForTableAttributePreparing;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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

it('preloads existing thumbnail indexes for the displayed assets', function (int $sourceWidth, int $sourceHeight, array $transforms) {
    Queue::fake();
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $folder = Folders::getRootFolderByVolumeId($volume->id);
    $imageTransformer = new ImageTransformer;

    foreach (range(1, 3) as $number) {
        $asset = Asset::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $folder->id,
            'filename' => "image-{$number}.jpg",
            'kind' => 'image',
            'width' => $sourceWidth,
            'height' => $sourceHeight,
            'dateModified' => now()->subMinute(),
        ]);

        foreach ($transforms as [$width, $height]) {
            $imageTransformer->getTransformIndex($asset, new ImageTransform([
                'width' => $width,
                'height' => $height,
                'mode' => 'fit',
            ]));
        }
    }

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        if (str_starts_with($query->sql, 'select') && str_contains($query->sql, Table::IMAGETRANSFORMINDEX)) {
            $queries[] = $query;
        }
    });

    $response = get(route('craft.cp.assets.index', ['defaultSource' => $volume->handle, 'per_page' => 2]), ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('component', 'assets/Index')
        ->assertJsonCount(2, 'props.data');

    expect($queries)->toHaveCount(2);

    $displayedIds = array_column($response->json('props.data'), 'id');
    sort($displayedIds);

    foreach ($queries as $query) {
        $queriedIds = array_values(array_filter($query->bindings, is_int(...)));
        sort($queriedIds);

        expect($queriedIds)->toBe($displayedIds);
    }

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->count())->toBe(6);
})->with([
    'landscape' => [800, 400, [[30, 30], [60, 60]]],
    'portrait' => [400, 600, [[30, 30], [60, 60]]],
    'square' => [600, 600, [[30, 30], [60, 60]]],
]);

it('preserves custom thumbnail URLs without resolving the configured transformer', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $asset = Asset::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => Folders::getRootFolderByVolumeId($volume->id)->id,
        'filename' => 'image.jpg',
        'kind' => 'image',
        'width' => 800,
        'height' => 400,
    ]);
    Cms::config()->defaultAssetTransformer('missing');
    $requests = [];
    Event::listen(ThumbUrlResolving::class, function (ThumbUrlResolving $event) use (&$requests): void {
        $requests[] = [$event->asset->id, $event->width, $event->height];
        $event->url = 'https://example.test/custom-thumb.jpg';
    });

    get(route('craft.cp.assets.index', ['defaultSource' => $volume->handle]), ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('props.data.0.title', fn (string $html): bool => str_contains($html, 'https://example.test/custom-thumb.jpg'));

    expect($requests)->toBe([[$asset->id, 30, 30], [$asset->id, 60, 60]]);
});

it('preserves thumbnail overrides on asset subclasses', function () {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    Asset::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => Folders::getRootFolderByVolumeId($volume->id)->id,
        'filename' => 'custom-thumbnail.jpg',
        'kind' => 'image',
        'width' => 800,
        'height' => 400,
    ]);
    Cms::config()->defaultAssetTransformer('missing');
    Event::listen(QueryForTableAttributePreparing::class, function (QueryForTableAttributePreparing $event): void {
        if ($event->elementType === AssetElement::class) {
            $event->query->elementType = CustomThumbnailIndexAsset::class;
        }
    });

    get(route('craft.cp.assets.index', ['defaultSource' => $volume->handle]), ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonCount(1, 'props.data')
        ->assertJsonPath('props.data.0.title', fn (string $html): bool => str_contains($html, 'https://example.test/subclass-thumbnail.jpg'));
});

it('reports thumbnail jobs created while rendering the requested page data', function (bool $inertia, bool $queueOnly) {
    $volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    Asset::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => Folders::getRootFolderByVolumeId($volume->id)->id,
        'filename' => 'missing-thumbnails.jpg',
        'kind' => 'image',
        'width' => 800,
        'height' => 400,
    ]);
    config(['queue.default' => 'database']);

    expect(DB::table(Table::JOBPROGRESS)->count())->toBe(0);

    $headers = $inertia ? ['X-Inertia' => 'true'] : [];
    if ($queueOnly) {
        $headers += [
            'X-Inertia-Partial-Component' => 'assets/Index',
            'X-Inertia-Partial-Data' => 'queue',
        ];
    }

    $response = get(route('craft.cp.assets.index', ['defaultSource' => $volume->handle]), $headers)->assertOk();

    if (! $inertia) {
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('queue.hasWaitingJobs', true)
            ->where('queue.hasReservedJobs', false)
            ->where('queue.displayedJob', fn ($job): bool => $job !== null)
            ->has('data', 1));

        expect(DB::table(Table::JOBPROGRESS)->count())->toBe(2);

        return;
    }

    $response->assertJsonPath('props.queue.hasWaitingJobs', ! $queueOnly)
        ->assertJsonPath('props.queue.hasReservedJobs', false);

    if ($queueOnly) {
        $response->assertJsonMissingPath('props.data')
            ->assertJsonPath('props.queue.displayedJob', null);
    } else {
        $response->assertJsonCount(1, 'props.data');

        expect($response->json('props.queue.displayedJob'))->not()->toBeNull();
    }

    expect(DB::table(Table::JOBPROGRESS)->count())->toBe($queueOnly ? 0 : 2);
})->with([
    'Inertia response' => [true, false],
    'initial HTML' => [false, false],
    'queue only' => [true, true],
]);

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

class CustomThumbnailIndexAsset extends AssetElement
{
    protected function thumbUrl(int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        return 'https://example.test/subclass-thumbnail.jpg';
    }
}
