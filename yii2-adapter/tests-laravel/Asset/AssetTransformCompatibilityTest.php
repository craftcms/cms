<?php

declare(strict_types=1);

use craft\base\Event;
use craft\elements\Asset as LegacyAsset;
use craft\events\DefineAssetUrlEvent;
use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Exceptions;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->driver = $driver = new CompatibilityAssetTransformDriver();
    app(AssetTransforms::class)->extend('compatibility-test', fn() => $driver);
    Cms::config()->defaultAssetTransformDriver('compatibility-test');
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

    expect($this->driver->request->settings)->toBe(['generateBeforePageLoad' => true]);

    $asset->getUrl(['width' => 320], false);

    expect($this->driver->request->settings)->toBe(['generateBeforePageLoad' => false]);
});

it('reports legacy URL failures and returns null', function(): void {
    Exceptions::fake();
    app(AssetTransforms::class)->extend('compatibility-test', fn() => new FailingCompatibilityAssetTransformDriver());
    $asset = ($this->asset)();

    expect($asset->getUrl(['width' => 320]))->toBeNull();
    Exceptions::assertReported(AssetTransformFailedException::class);
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
