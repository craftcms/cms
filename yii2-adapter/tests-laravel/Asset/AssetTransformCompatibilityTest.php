<?php

declare(strict_types=1);

use craft\base\Event;
use craft\base\imagetransforms\EagerImageTransformerInterface as LegacyEagerImageTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface as LegacyImageTransformerInterface;
use craft\elements\Asset as LegacyAsset;
use craft\events\DefineAssetUrlEvent;
use craft\events\GenerateTransformEvent;
use craft\events\ImageTransformerOperationEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\imagetransforms\ImageTransformer as LegacyCraftImageTransformer;
use craft\models\ImageTransform as LegacyImageTransform;
use craft\services\ImageTransforms as LegacyImageTransforms;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\DeletingTransformedImage;
use CraftCms\Cms\Image\Events\ImageTransforming;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\Asset\ImageTransformers;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->driver = $driver = new CompatibilityAssetTransformDriver();
    app(AssetTransformDrivers::class)->extend('compatibility-test', fn() => $driver);
    app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Compatibility test',
        'handle' => 'compatibility-test',
        'driver' => 'compatibility-test',
    ]), false);
    Cms::config()->defaultAssetProcessor('compatibility-test');
    $this->asset = function(array $attributes = []): LegacyAsset {
        $model = AssetModel::factory()->create([
            ...$attributes,
            'id' => Element::factory()->set('type', LegacyAsset::class),
        ]);

        return LegacyAsset::find()->id($model->id)->one();
    };
});

it('provides mutable transform state and transformed copies', function(): void {
    $asset = ($this->asset)([
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);

    expect($asset->setTransform(['width' => 320]))->toBe($asset)
        ->and($asset->getUrl())->toBe('/renditions/320x160.webp')
        ->and($asset->getWidth())->toBe(320)
        ->and($asset->getHeight())->toBe(160)
        ->and($asset->getMimeType())->toBe('image/webp')
        ->and((string) $asset->getImg())->toContain('src="/renditions/320x160.webp"')
        ->and($asset->getSrcset(['1x']))->toBe('/renditions/320x160.webp')
        ->and((string) $asset)->toBe('/renditions/320x160.webp');

    $copy = $asset->copyWithTransform(['width' => 640]);

    expect($copy)->not->toBe($asset)
        ->and($copy->getUrl())->toBe('/renditions/640x320.webp')
        ->and($asset->getUrl())->toBe('/renditions/320x160.webp');
});

it('provides named transform magic properties', function(): void {
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Card',
        'handle' => 'card',
        'width' => 400,
        'height' => 200,
    ]));
    $asset = ($this->asset)();

    expect(isset($asset->card))->toBeTrue()
        ->and($asset->card)->toBeInstanceOf($asset::class)
        ->and($asset->card->getUrl())->toBe('/renditions/400x200.webp')
        ->and($asset->{'transform:card'}->getUrl())->toBe('/renditions/400x200.webp');
});

it('returns legacy models from named transform getters', function(): void {
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Legacy Card',
        'handle' => 'legacyCard',
        'width' => 400,
    ]));
    $service = Craft::$app->getImageTransforms();
    $transform = $service->getTransformByHandle('legacyCard');

    expect($transform)->toBeInstanceOf(LegacyImageTransform::class)
        ->and($service->getTransformById($transform->id))->toBeInstanceOf(LegacyImageTransform::class)
        ->and($service->getTransformByUid($transform->uid))->toBeInstanceOf(LegacyImageTransform::class)
        ->and($service->getAllTransforms())->each->toBeInstanceOf(LegacyImageTransform::class);
});

it('preloads registered legacy eager transformers through adapter drivers', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = RegisteredLegacyEagerImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(RegisteredLegacyEagerImageTransformer::class);

    try {
        Craft::$app->getImageTransforms()->eagerLoadTransforms([$asset], [$transform]);
        $transformer = Craft::$app->getImageTransforms()->getImageTransformer(RegisteredLegacyEagerImageTransformer::class);

        expect($transformer->assets)->toBe([$asset])
            ->and($transformer->transforms)->toHaveCount(1)
            ->and($transformer->transforms[0])->toBeInstanceOf(LegacyImageTransform::class)
            ->and($transformer->transforms[0]->width)->toBe(320);
    } finally {
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('preserves legacy URL event order and handled null semantics', function(): void {
    $asset = ($this->asset)();
    $events = [];
    $payload = null;
    $definedPayload = null;
    $before = function(DefineAssetUrlEvent $event) use (&$events, &$payload): void {
        $events[] = 'before';
        $payload = [$event->asset, $event->sender, $event->transform];
        $event->url = null;
    };
    $after = function(DefineAssetUrlEvent $event) use (&$events, &$definedPayload): void {
        $events[] = 'after';
        $definedPayload = [$event->asset, $event->sender, $event->transform, $event->url];
        $event->url = null;
        $event->handled = true;
    };
    Event::on(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_DEFINE_URL, $before);
    Event::on(LegacyAsset::class, LegacyAsset::EVENT_DEFINE_URL, $after);

    try {
        expect($asset->getUrl(['width' => 320]))->toBeNull()
            ->and($events)->toBe(['before', 'after'])
            ->and($payload)->toBe([$asset, $asset, ['width' => 320]])
            ->and($definedPayload)->toBe([$asset, $asset, ['width' => 320], '/renditions/320x160.webp']);
    } finally {
        Event::off(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_DEFINE_URL, $before);
        Event::off(LegacyAsset::class, LegacyAsset::EVENT_DEFINE_URL, $after);
    }
});

it('applies the nullable legacy generation policy and immediate overrides', function(): void {
    Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = true;
    $asset = ($this->asset)();

    $asset->getUrl(['width' => 320]);

    expect($this->driver->request->immediately)->toBeTrue();

    $asset->getUrl(['width' => 320], false);

    expect($this->driver->request->immediately)->toBeFalse();
});

it('applies the legacy generation policy to control panel thumbnails', function(): void {
    Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = true;
    $asset = ($this->asset)();

    app(Assets::class)->getThumbUrl($asset, 320, 160);

    expect($this->driver->request->immediately)->toBeTrue();
});

it('reports legacy URL failures and returns null', function(): void {
    Exceptions::fake();
    app(AssetTransformDrivers::class)->extend('compatibility-test', fn() => new FailingCompatibilityAssetTransformDriver());
    $asset = ($this->asset)();

    expect($asset->getUrl(['width' => 320]))->toBeNull();
    Exceptions::assertReported(AssetTransformFailedException::class);
});

it('routes registered legacy image transformers through adapter drivers', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = RegisteredLegacyImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    $asset = ($this->asset)([
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);
    $transform = [
        'width' => 320,
        'transformer' => RegisteredLegacyImageTransformer::class,
    ];
    $generatedUrl = null;
    $after = function(GenerateTransformEvent $event) use (&$generatedUrl): void {
        $generatedUrl = $event->url;
    };
    Event::on(LegacyAsset::class, LegacyAsset::EVENT_AFTER_GENERATE_TRANSFORM, $after);

    try {
        expect($asset->getUrl($transform, false))->toBe('/legacy/320%20image.jpg');

        $transformer = Craft::$app->getImageTransforms()->getImageTransformer(RegisteredLegacyImageTransformer::class);

        expect($transformer->asset)->toBe($asset)
            ->and($transformer->transform)->toBeInstanceOf(ImageTransform::class)
            ->and($transformer->transform->width)->toBe(320)
            ->and($transformer->immediately)->toBeFalse()
            ->and($generatedUrl)->toBe('/legacy/320%20image.jpg');
    } finally {
        Event::off(LegacyAsset::class, LegacyAsset::EVENT_AFTER_GENERATE_TRANSFORM, $after);
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('selects the volume before the legacy transformer candidate', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = RegisteredLegacyImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    config()->set('filesystems.disks.legacy-precedence-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/legacy-precedence-source'),
    ]);
    $volume = Volume::factory()->create([
        'fs' => 'disk:legacy-precedence-source',
        'assetProcessor' => 'compatibility-test',
    ]);
    $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id]);
    $asset = ($this->asset)([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(RegisteredLegacyImageTransformer::class);
    $before = function(GenerateTransformEvent $event): void {
        $event->url = '/legacy/event.jpg';
    };
    Event::on(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_GENERATE_TRANSFORM, $before);

    try {
        expect($asset->getUrl($transform))->toBe('/renditions/320x160.webp')
            ->and($this->driver->request)->not->toBeNull()
            ->and(Craft::$app->getImageTransforms()->getImageTransformer(RegisteredLegacyImageTransformer::class)->asset)->toBeNull();
    } finally {
        Event::off(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_GENERATE_TRANSFORM, $before);
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('does not use the legacy selector for typed transform calls', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = RegisteredLegacyImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(RegisteredLegacyImageTransformer::class);

    try {
        expect($asset->transform($transform)->url)->toBe('/renditions/320x160.webp')
            ->and(app(AssetProcessors::class)->transform($asset, $transform)->url)->toBe('/renditions/320x160.webp')
            ->and($asset->getUrl(['transformer' => 'compatibility-test', 'transform' => $transform]))->toBe('/renditions/320x160.webp')
            ->and(Craft::$app->getImageTransforms()->getImageTransformer(RegisteredLegacyImageTransformer::class)->asset)->toBeNull();
    } finally {
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('does not use the legacy selector for typed calls from URL listeners', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = RegisteredLegacyImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(RegisteredLegacyImageTransformer::class);
    $typedUrl = null;
    EventFacade::listen(AssetUrlResolving::class, function(AssetUrlResolving $event) use ($transform, &$typedUrl): void {
        $typedUrl = $event->asset->transform($transform)->url;
        $event->url = '/resolved.jpg';
        $event->handled = true;
    });

    try {
        expect($asset->getUrl($transform))->toBe('/resolved.jpg')
            ->and($typedUrl)->toBe('/renditions/320x160.webp')
            ->and(Craft::$app->getImageTransforms()->getImageTransformer(RegisteredLegacyImageTransformer::class)->asset)->toBeNull();
    } finally {
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('lazily bridges a selected legacy transformer that was not registered', function(): void {
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(UnregisteredLegacyImageTransformer::class);

    expect($asset->getUrl($transform))->toBe('/legacy/320%20image.jpg');
    app(AssetProcessors::class)->invalidate($asset);

    expect(app(ImageTransformers::class)->types())->toContain(UnregisteredLegacyImageTransformer::class)
        ->and($this->driver->request)->toBeNull()
        ->and(Craft::$app->getImageTransforms()->getImageTransformer(UnregisteredLegacyImageTransformer::class)->asset)->toBe($asset)
        ->and(Craft::$app->getImageTransforms()->getImageTransformer(UnregisteredLegacyImageTransformer::class)->invalidatedAsset)->toBe($asset);
});

it('honors before-generate URLs before invoking a legacy transformer', function(): void {
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(UnregisteredLegacyImageTransformer::class);
    $before = function(GenerateTransformEvent $event): void {
        $event->url = '/legacy/event.jpg';
    };
    Event::on(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_GENERATE_TRANSFORM, $before);

    try {
        expect($asset->getUrl($transform))->toBe('/legacy/event.jpg')
            ->and($this->driver->request)->toBeNull();
    } finally {
        Event::off(LegacyAsset::class, LegacyAsset::EVENT_BEFORE_GENERATE_TRANSFORM, $before);
    }
});

it('keeps unexpected legacy transformer exceptions observable', function(): void {
    Event::on(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS, function(RegisterComponentTypesEvent $event): void {
        $event->types[] = ThrowingLegacyImageTransformer::class;
    });
    LegacyImageTransforms::finalizeRegistrationEvents();
    $asset = ($this->asset)();
    $transform = new LegacyImageTransform(['width' => 320]);
    $transform->setTransformer(ThrowingLegacyImageTransformer::class);

    try {
        expect(fn() => $asset->getUrl($transform))->toThrow(RuntimeException::class, 'Unexpected legacy failure');
    } finally {
        Event::off(LegacyImageTransforms::class, LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS);
    }
});

it('keeps the built-in Craft transform index facade scoped to Craft rows', function(): void {
    $asset = ($this->asset)(['filename' => 'indexed.jpg']);
    $transformer = new LegacyCraftImageTransformer();
    $index = $transformer->getTransformIndex($asset, ['width' => 320]);
    $foreignId = DB::table(Table::IMAGETRANSFORMINDEX)->insertGetId([
        'assetId' => $asset->id,
        'transformer' => 'plugin\\ForeignTransformer',
        'filename' => 'foreign.jpg',
        'transformString' => '_320x_auto',
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => (string) str()->uuid(),
    ]);
    $index->fileExists = true;
    $transformer->storeTransformIndexData($index);
    $foreignIndex = new ImageTransformIndex([
        'id' => $foreignId,
        'assetId' => $asset->id,
        'transformer' => 'plugin\\ForeignTransformer',
        'filename' => 'foreign.jpg',
        'transformString' => '_320x_auto',
        'fileExists' => true,
    ]);
    $foreignIndex->setTransform(new ImageTransform(['width' => 320]));
    $transformer->storeTransformIndexData($foreignIndex);

    expect($transformer->getTransformIndexModelById($index->id)->fileExists)->toBeTrue()
        ->and($index->transformer)->toBe(app(AssetProcessors::class)->resolve('craft')->uid)
        ->and($transformer->getTransformIndexModelById($foreignId))->toBeNull()
        ->and($transformer->getPendingTransformIndexIds())->not->toContain($foreignId)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $foreignId)->value('fileExists'))->toBe(0);

    $transformer->invalidateAssetTransforms($asset);

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $foreignId)->exists())->toBeTrue();
});

it('bridges mutable built-in transform event payloads', function(): void {
    $asset = ($this->asset)(['filename' => 'event.jpg']);
    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'filename' => 'event.jpg',
        'transformString' => '_320x_auto',
    ]);
    $transform = new ImageTransform(['width' => 320]);
    $legacyTransformer = Craft::$app->getImageTransforms()->getImageTransformer(LegacyCraftImageTransformer::class);
    $payload = null;
    $listener = function(ImageTransformerOperationEvent $event) use (&$payload): void {
        $payload = $event;
        $event->tempPath = '/tmp/replaced.jpg';
    };
    Event::on(LegacyCraftImageTransformer::class, LegacyCraftImageTransformer::EVENT_TRANSFORM_IMAGE, $listener);

    try {
        $event = new ImageTransforming(
            asset: $asset,
            imageTransformIndex: $index,
            transform: $transform,
            path: 'transforms/event.jpg',
            tempPath: '/tmp/original.jpg',
        );

        event($event);

        expect($payload->asset)->toBe($asset)
            ->and($payload->imageTransformIndex)->toBe($index)
            ->and($payload->path)->toBe('transforms/event.jpg')
            ->and($payload->tempPath)->toBe('/tmp/replaced.jpg')
            ->and($event->tempPath)->toBe('/tmp/replaced.jpg');
    } finally {
        Event::off(LegacyCraftImageTransformer::class, LegacyCraftImageTransformer::EVENT_TRANSFORM_IMAGE, $listener);
    }
});

it('bridges built-in transformed-image deletion events', function(): void {
    $asset = ($this->asset)(['filename' => 'deleted-transform.jpg']);
    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'filename' => 'deleted-transform.jpg',
        'transformString' => '_320x_auto',
    ]);
    Craft::$app->getImageTransforms()->getImageTransformer(LegacyCraftImageTransformer::class);
    $payload = null;
    $listener = function(ImageTransformerOperationEvent $event) use (&$payload): void {
        $payload = $event;
    };
    Event::on(LegacyCraftImageTransformer::class, LegacyCraftImageTransformer::EVENT_DELETE_TRANSFORMED_IMAGE, $listener);

    try {
        event(new DeletingTransformedImage(
            asset: $asset,
            imageTransformIndex: $index,
            path: 'transforms/deleted-transform.jpg',
        ));

        expect($payload->asset)->toBe($asset)
            ->and($payload->imageTransformIndex)->toBe($index)
            ->and($payload->path)->toBe('transforms/deleted-transform.jpg');
    } finally {
        Event::off(LegacyCraftImageTransformer::class, LegacyCraftImageTransformer::EVENT_DELETE_TRANSFORMED_IMAGE, $listener);
    }
});

it('keeps deprecated transform destinations distinct per Volume', function(): void {
    $first = Volume::factory()->create();
    $second = Volume::factory()->create([
        'fs' => $first->fs,
    ]);
    $volumes = app(Volumes::class);
    $firstVolume = $volumes->getVolumeById($first->id);
    $secondVolume = $volumes->getVolumeById($second->id);
    $firstVolume->setTransformFsHandle('legacy-first');
    $firstVolume->setTransformSubpath('first-renditions');
    $secondVolume->setTransformFsHandle('legacy-second');
    $secondVolume->setTransformSubpath('second-renditions');
    $volumes->saveVolume($firstVolume, false);
    $volumes->saveVolume($secondVolume, false);
    $volumes->reset();

    $firstVolume = $volumes->getVolumeById($first->id);
    $secondVolume = $volumes->getVolumeById($second->id);

    expect($firstVolume->getTransformFsHandle(false))->toBe('legacy-first')
        ->and($firstVolume->getTransformSubpath(false, false))->toBe('first-renditions')
        ->and($secondVolume->getTransformFsHandle(false))->toBe('legacy-second')
        ->and($secondVolume->getTransformSubpath(false, false))->toBe('second-renditions')
        ->and($firstVolume->getConfig())->toMatchArray([
            'transformFs' => 'legacy-first',
            'transformSubpath' => 'first-renditions',
        ]);

    $firstVolume->name = 'Updated Volume';
    $volumes->saveVolume($firstVolume);

    expect(app(ProjectConfig::class)->get(ProjectConfig::PATH_VOLUMES . '.' . $first->uid))
        ->toMatchArray([
            'transformFs' => 'legacy-first',
            'transformSubpath' => 'first-renditions',
        ]);
});

it('provides deprecated Volume transform filesystem methods through the adapter', function(): void {
    config()->set('filesystems.disks.legacy-volume-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/legacy-volume-source'),
    ]);
    config()->set('filesystems.disks.legacy-volume-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/legacy-volume-target'),
        'url' => 'https://legacy-volume.example.test',
    ]);
    Storage::disk('legacy-volume-target')->deleteDirectory('');

    $volume = new VolumeData([
        'name' => 'Legacy Volume',
        'handle' => 'legacyVolume',
        'fs' => 'legacy-volume-source',
        'transformFs' => 'legacy-volume-target',
        'transformSubpath' => 'renditions',
    ]);

    expect($volume->getTransformFsHandle(false))->toBe('disk:legacy-volume-target')
        ->and($volume->getTransformSubpath())->toBe('renditions/')
        ->and($volume->transformHasUrls())->toBeTrue()
        ->and($volume->transformDisk()->put('image.jpg', 'image'))->toBeTrue()
        ->and(Storage::disk('legacy-volume-target')->exists('renditions/image.jpg'))->toBeTrue();
});

class CompatibilityAssetTransformDriver implements AssetTransformDriver
{
    public ?AssetTransformRequest $request = null;

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('Compatibility test');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->request = $request;
        $width = $request->operations['width'] ?? 800;
        $height = $request->operations['height'] ?? (int) ($width / 2);

        return new AssetTransformResult(
            url: "/renditions/{$width}x{$height}.webp",
            mimeType: 'image/webp',
            width: $width,
            height: $height,
        );
    }
}

class FailingCompatibilityAssetTransformDriver extends CompatibilityAssetTransformDriver
{
    #[Override]
    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        throw new AssetTransformFailedException('Failed');
    }
}

class RegisteredLegacyImageTransformer implements LegacyImageTransformerInterface
{
    public ?Asset $asset = null;

    public ?Asset $invalidatedAsset = null;

    public ?ImageTransform $transform = null;

    public ?bool $immediately = null;

    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        $this->asset = $asset;
        $this->transform = $imageTransform;
        $this->immediately = $immediately;

        return "/legacy/{$imageTransform->width} image.jpg";
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        $this->invalidatedAsset = $asset;
    }
}

class UnregisteredLegacyImageTransformer extends RegisteredLegacyImageTransformer
{
}

class RegisteredLegacyEagerImageTransformer extends RegisteredLegacyImageTransformer implements LegacyEagerImageTransformerInterface
{
    /** @var LegacyImageTransform[] */
    public array $transforms = [];

    /** @var Asset[] */
    public array $assets = [];

    public function eagerLoadTransforms(array $transforms, array $assets): void
    {
        $this->transforms = $transforms;
        $this->assets = $assets;
    }
}

class ThrowingLegacyImageTransformer extends RegisteredLegacyImageTransformer
{
    #[Override]
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        throw new RuntimeException('Unexpected legacy failure');
    }
}
