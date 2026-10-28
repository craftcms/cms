<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Analysis\Jobs\AnalyzeImage;
use CraftCms\Cms\Asset\AssetUploadHandler;
use CraftCms\Cms\Asset\Data\AssetIngest;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Image\Data\ImageMetadata;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalFlysystemAdapter;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config()->set('filesystems.disks.asset-ingest-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-ingest-source'),
    ]);
    config()->set('filesystems.disks.asset-ingest-destination', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-ingest-destination'),
    ]);
    Storage::fake('asset-ingest-source');
    Storage::fake('asset-ingest-destination');

    $this->field = Field::factory()->create([
        'handle' => 'assetCaption',
        'type' => PlainText::class,
        'settings' => ['charLimit' => 10],
    ]);
    $fieldLayout = FieldLayout::factory()
        ->forField($this->field)
        ->create(['type' => Asset::class]);

    $this->volume = Volume::factory()->create([
        'fs' => 'asset-ingest-destination',
        'fieldLayoutId' => $fieldLayout->id,
    ]);
    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $folderModel = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'path' => '',
    ]);
    $this->folder = app(Folders::class)->getFolderById($folderModel->id);
    $this->source = function (string $path, string $contents): UploadedFile {
        Storage::disk('asset-ingest-source')->put($path, $contents);

        return new UploadedFile(Storage::disk('asset-ingest-source'), $path, basename($path));
    };
});

it('ingests a filesystem-backed file without an HTTP request', function () {
    $uploader = User::firstOrFail();

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/source.bin', 'asset contents'),
        filename: 'Proof Of Address.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
        uploaderId: $uploader->id,
    ));

    $asset = Asset::findOne($result->asset->id);

    expect($result->status)->toBe(AssetIngestStatus::Saved)
        ->and($asset)->not->toBeNull()
        ->and($asset->getFilename())->toBe('Proof-Of-Address.txt')
        ->and($asset->getMimeType())->toBe('text/plain')
        ->and($asset->uploaderId)->toBe($uploader->id)
        ->and(Storage::disk('asset-ingest-destination')->get($asset->getPath()))->toBe('asset contents');
});

it('saves and returns a prepared asset with its metadata and field values', function () {
    $preparedAsset = new Asset;
    $preparedAsset->title = 'Prepared title';
    $preparedAsset->alt = 'Prepared alternative text';
    $preparedAsset->enabled = false;
    $preparedAsset->siteId = app(Sites::class)->getPrimarySite()->id;
    $preparedAsset->setFieldValue($this->field->handle, 'Caption');

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/prepared.txt', 'asset contents'),
        filename: 'prepared.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
        asset: $preparedAsset,
    ));

    $savedAsset = Asset::find()
        ->id($preparedAsset->id)
        ->siteId($preparedAsset->siteId)
        ->status(null)
        ->one();

    expect($result->status)->toBe(AssetIngestStatus::Saved)
        ->and($result->asset)->toBe($preparedAsset)
        ->and($savedAsset)->not->toBeNull()
        ->and($savedAsset->title)->toBe('Prepared title')
        ->and($savedAsset->alt)->toBe('Prepared alternative text')
        ->and($savedAsset->enabled)->toBeFalse()
        ->and($savedAsset->siteId)->toBe($preparedAsset->siteId)
        ->and($savedAsset->getVolumeId())->toBe($this->volume->id)
        ->and($savedAsset->getFieldLayout()?->id)->toBe($this->volume->fieldLayoutId)
        ->and($savedAsset->getFieldValue($this->field->handle))->toBe('Caption');
});

it('does not persist a prepared asset with invalid metadata or field values', function (string $attribute, string $value) {
    $preparedAsset = new Asset;
    $preparedAsset->title = 'Valid title';
    $preparedAsset->setFieldValue($this->field->handle, 'Valid');

    if ($attribute === 'title') {
        $preparedAsset->title = $value;
    } else {
        $preparedAsset->setFieldValue($attribute, $value);
    }

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)("decoded/invalid-$attribute.txt", 'asset contents'),
        filename: "invalid-$attribute.txt",
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
        asset: $preparedAsset,
    ));

    expect($result->status)->toBe(AssetIngestStatus::Invalid)
        ->and($result->asset)->toBe($preparedAsset)
        ->and($preparedAsset->errors()->has($attribute))->toBeTrue()
        ->and(Asset::find()->count())->toBe(0);
    Storage::disk('asset-ingest-destination')->assertDirectoryEmpty('/');
})->with([
    'invalid title' => ['title', str_repeat('x', 256)],
    'invalid custom field' => ['assetCaption', str_repeat('x', 11)],
]);

it('rejects an ingest target without a persisted location under the create scenario', function () {
    $folder = new VolumeFolder;
    $folder->volumeId = $this->volume->id;
    $folder->path = '';

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/source.txt', 'asset contents'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $folder,
        sanitizeOnUpload: false,
    ));

    expect($result->status)->toBe(AssetIngestStatus::Invalid)
        ->and($result->asset->errors()->has('newLocation'))->toBeTrue()
        ->and(Asset::find()->count())->toBe(0);
    Storage::disk('asset-ingest-destination')->assertDirectoryEmpty('/');
});

it('sanitizes images while ingesting them under the create scenario', function () {
    $unsafeSvg = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">
            <script>alert('unsafe')</script>
            <rect width="10" height="10" fill="red" />
        </svg>
        SVG;

    $result = app(AssetUploadHandler::class)->ingest(new AssetIngest(
        source: ($this->source)('decoded/unsafe.svg', $unsafeSvg),
        filename: 'unsafe.svg',
        mimeType: 'image/svg+xml',
        folder: $this->folder,
        sanitizeOnUpload: true,
    ));

    expect($result->status)->toBe(AssetIngestStatus::Saved)
        ->and(Storage::disk('asset-ingest-destination')->get($result->asset->getPath()))->not->toContain('<script');
});

it('returns the existing asset when ingest resolves a filename conflict', function () {
    $uploads = app(AssetUploadHandler::class);
    $first = $uploads->ingest(new AssetIngest(
        source: ($this->source)('decoded/first.txt', 'first'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
    ));
    $second = $uploads->ingest(new AssetIngest(
        source: ($this->source)('decoded/second.txt', 'second'),
        filename: 'example.txt',
        mimeType: 'text/plain',
        folder: $this->folder,
        sanitizeOnUpload: false,
    ));

    expect($second->status)->toBe(AssetIngestStatus::Saved)
        ->and($second->asset->conflictingFilename)->toBe('example.txt')
        ->and($second->asset->getFilename())->not->toBe('example.txt')
        ->and($second->conflictingAsset?->id)->toBe($first->asset->id)
        ->and(Asset::find()->folderId($this->folder->id)->count())->toBe(2);
});

it('saves uploaded assets in the site they were uploaded from', function () {
    actingAs(UserElement::findOne());
    $secondSite = SiteModel::factory()->create();
    app(Sites::class)->refreshSites();

    $result = app(AssetUploadHandler::class)->store(
        ['folderId' => $this->folder->id, 'siteId' => $secondSite->id],
        ($this->source)('uploads/site.txt', 'site contents'),
    );

    expect($result->assetId)->not->toBeNull()
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $result->assetId)->pluck('siteId')->all())->toBe([$secondSite->id]);
});

it('ignores upload site IDs the user can’t edit', function () {
    actingAs(UserElement::findOne());

    $result = app(AssetUploadHandler::class)->store(
        ['folderId' => $this->folder->id, 'siteId' => 999],
        ($this->source)('uploads/site.txt', 'site contents'),
    );

    expect(DB::table(Table::ELEMENTS_SITES)->where('elementId', $result->assetId)->pluck('siteId')->all())
        ->toBe([app(Sites::class)->getPrimarySite()->id]);
});

describe('images staged on a remote filesystem', function () {
    beforeEach(function () {
        Queue::fake();
        $root = storage_path('framework/testing/asset-ingest-remote');
        File::ensureDirectoryExists($root);
        File::cleanDirectory($root);

        // Anything but Laravel's local adapter counts as remote, as an S3 bucket a file was uploaded straight to does
        Storage::extend('asset-ingest-remote', function ($app, array $config) {
            $adapter = new LocalFlysystemAdapter($config['root']);

            return new FilesystemAdapter(new Flysystem($adapter, $config), $adapter, $config);
        });
        config()->set('filesystems.disks.asset-ingest-remote', ['driver' => 'asset-ingest-remote', 'root' => $root]);

        $this->ingestImage = function (bool $sanitize, ?ImageAnalysis $analysis = null): Asset {
            $disk = Storage::disk('asset-ingest-remote');
            $disk->put('staged/photo.jpg', file_get_contents(metadataJpeg(xmpPacket(
                '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">Boats at dusk</rdf:li></rdf:Alt></dc:description>'
            ))));
            deleteMetadataJpegs();
            $this->staged = Mockery::mock(UploadedFile::class, [$disk, 'staged/photo.jpg', 'photo.jpg'])->makePartial();

            return app(AssetUploadHandler::class)->ingest(new AssetIngest(
                source: $this->staged,
                filename: 'photo.jpg',
                mimeType: 'image/jpeg',
                folder: $this->folder,
                sanitizeOnUpload: $sanitize,
                analysis: $analysis,
            ))->asset;
        };
    });

    it('store what the uploader read from them, without being downloaded or queued', function () {
        $colors = ImageColors::fromArray(['dominant' => '#3a6ea5', 'grid' => [['#3a6ea5']], 'palette' => ['#3a6ea5'], 'luminance' => [[[0.1, 0.2]]], 'weights' => [1.0]]);
        $metadata = new ImageMetadata(description: 'Boats at dusk');

        $asset = ($this->ingestImage)(sanitize: false, analysis: new ImageAnalysis(colors: $colors, hash: 'fcfbf8e7ecdad4a3', metadata: $metadata));

        $this->staged->shouldNotHaveReceived('localPath');
        expect($asset->analysis?->colors)->toEqual($colors)
            ->and($asset->analysis?->hash)->toBe('fcfbf8e7ecdad4a3')
            ->and($asset->analysis?->metadata)->toEqual($metadata)
            ->and($asset->alt)->toBe('Boats at dusk')
            ->and($asset->getWidth())->toBe(1);
        Queue::assertNotPushed(AnalyzeImage::class);
    });

    it('aren’t downloaded without what the uploader read from them, and are sampled by a queue job instead', function () {
        $asset = ($this->ingestImage)(sanitize: false);

        $this->staged->shouldNotHaveReceived('localPath');
        expect($asset->getWidth())->toBe(1)
            ->and($asset->getHeight())->toBe(1)
            ->and($asset->analysis)->toBeNull()
            ->and(Storage::disk('asset-ingest-destination')->get($asset->getPath()))->toContain('Boats at dusk');
        Queue::assertPushed(AnalyzeImage::class, fn (AnalyzeImage $job): bool => $job->assetId === $asset->id);
    });

    it('are sampled before they’re cleaned, and only queued for AI tasks', function () {
        $asset = ($this->ingestImage)(sanitize: true);

        $this->staged->shouldHaveReceived('localPath');
        expect($asset->analysis?->colors?->grid)->not->toBe([])
            ->and($asset->analysis?->metadata?->description)->toBe('Boats at dusk')
            ->and($asset->alt)->toBe('Boats at dusk');
        Queue::assertNotPushed(AnalyzeImage::class);
    });
});
