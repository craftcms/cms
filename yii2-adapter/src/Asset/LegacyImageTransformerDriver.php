<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use Craft;
use craft\base\imagetransforms\EagerImageTransformerInterface;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\models\ImageTransform;
use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AfterGenerateTransform;
use CraftCms\Cms\Asset\Events\TransformGenerating;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;
use LogicException;

/** @internal */
class LegacyImageTransformerDriver implements AssetProcessorDriver, PreloadsAssetTransforms
{
    public function __construct(private readonly string $transformer)
    {
    }

    public function definition(): AssetProcessorDriverDefinition
    {
        return new AssetProcessorDriverDefinition($this->transformer);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if (!is_a($this->transformer, ImageTransformerInterface::class, true)) {
            throw new LogicException("Legacy image transformer [{$this->transformer}] is invalid.");
        }

        $transform = new ImageTransform($request->operations);

        event($event = new TransformGenerating($request->asset, $transform));

        if ($event->url !== null) {
            return self::result($request->asset, $transform, Html::encodeSpaces($event->url));
        }

        $url = Craft::$app->getImageTransforms()
            ->getImageTransformer($this->transformer)
            ->getTransformUrl($request->asset, $transform, $request->immediately);
        $url = Html::encodeSpaces($url);

        event(new AfterGenerateTransform($request->asset, $transform, $url));

        return self::result($request->asset, $transform, $url);
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
            $assets[$request->asset->id ?? spl_object_id($request->asset)] = $request->asset;
            $transforms[serialize($request->operations)] ??= new ImageTransform($request->operations);
        }

        $transformer->eagerLoadTransforms(array_values($transforms), array_values($assets));
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
}
