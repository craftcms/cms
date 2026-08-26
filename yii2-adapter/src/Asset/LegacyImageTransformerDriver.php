<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use Craft;
use craft\base\imagetransforms\EagerImageTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset as LegacyAsset;
use craft\models\ImageTransform;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use LogicException;

/** @internal */
class LegacyImageTransformerDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    public function __construct(private readonly string $transformer)
    {
    }

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition($this->transformer);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if (!is_a($this->transformer, ImageTransformerInterface::class, true)) {
            throw new LogicException("Legacy image transformer [{$this->transformer}] is invalid.");
        }

        $asset = $this->legacyAsset($request->asset);
        $transform = new ImageTransform()->setInlineParameters($request->parameters);

        event($event = new TransformGenerating($asset, $transform));

        if ($event->url !== null) {
            return self::result($asset, $transform, Html::encodeSpaces($event->url));
        }

        $url = Craft::$app->getImageTransforms()
            ->getImageTransformer($this->transformer)
            ->getTransformUrl($asset, $transform, $request->immediately);
        $url = Html::encodeSpaces($url);

        event(new AfterGenerateTransform($asset, $transform, $url));

        return self::result($asset, $transform, $url);
    }

    /** @param non-empty-list<AssetTransformRequest> $requests */
    public function preloadAssetTransforms(array $requests): void
    {
        $transformer = Craft::$app->getImageTransforms()->getImageTransformer($this->transformer);

        if (!$transformer instanceof EagerImageTransformerInterface) {
            return;
        }

        $assets = [];
        $transforms = [];

        foreach ($requests as $request) {
            $asset = $this->legacyAsset($request->asset);
            $assets[$asset->id ?? spl_object_id($asset)] = $asset;
            $transforms[serialize($request->parameters)] ??= new ImageTransform()->setInlineParameters($request->parameters);
        }

        $transformer->eagerLoadTransforms(array_values($transforms), array_values($assets));
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        Craft::$app->getImageTransforms()
            ->getImageTransformer($this->transformer)
            ->invalidateAssetTransforms($this->legacyAsset($asset));
    }

    public static function result(Asset $asset, ImageTransform $transform, string $url): AssetTransformResult
    {
        $path = parse_url($url, PHP_URL_PATH);
        $mimeType = is_string($path) ? File::getMimeTypeByExtension($path) : null;
        $format = $transform->format ?? $asset->getExtension();
        $mimeType ??= File::getMimeTypeByExtension("file.{$format}");

        if (!str_starts_with($mimeType ?? '', 'image/')) {
            $mimeType = 'image/jpeg';
        }

        return new AssetTransformResult($url, $mimeType);
    }

    private function legacyAsset(Asset $asset): LegacyAsset
    {
        if ($asset instanceof LegacyAsset) {
            return $asset;
        }

        $legacyAsset = LegacyAsset::find()
            ->id($asset->id)
            ->siteId($asset->siteId)
            ->status(null)
            ->one();

        if (!$legacyAsset instanceof LegacyAsset) {
            throw new LogicException("Unable to resolve legacy Asset [{$asset->id}].");
        }

        return $legacyAsset;
    }
}
