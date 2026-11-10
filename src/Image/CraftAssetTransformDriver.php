<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Context;

/**
 * @since 6.0.0
 */
class CraftAssetTransformDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    public const string IMMEDIATE_TRANSFORMS_CONTEXT = self::class.'.immediateTransforms';

    public function __construct(private readonly ImageTransformer $imageTransformer) {}

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition;
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if (! ImageHelper::canManipulateAsImage($request->asset->getExtension())) {
            throw new NotSupportedException('The Asset cannot be manipulated as an image.');
        }

        $transform = ImageTransform::fromParameters($request->parameters);
        $immediately = Context::hasHidden(self::IMMEDIATE_TRANSFORMS_CONTEXT)
            ? Context::getHidden(self::IMMEDIATE_TRANSFORMS_CONTEXT) === true
            : ($request->transformer->settings['generate_transforms_before_page_load'] ?? false) === true;
        try {
            $url = $this->imageTransformer->getTransformUrl($request->asset, $transform, $immediately, $request->transformer);
        } catch (ImageTransformException $exception) {
            throw new AssetTransformFailedException($exception->getMessage(), previous: $exception);
        }

        $format = $transform->format ?? ImageTransformHelper::detectTransformFormat($request->asset);
        $source = clone $request->asset;
        if (method_exists($source, 'setTransform')) {
            $source->setTransform(null);
        }
        $sourceWidth = $source->getWidth();
        $sourceHeight = $source->getHeight();
        [$width, $height] = $sourceWidth && $sourceHeight
            ? ImageHelper::targetDimensions(
                $sourceWidth,
                $sourceHeight,
                $transform->width !== null ? (int) $transform->width : null,
                $transform->height !== null ? (int) $transform->height : null,
                $transform->mode,
                $transform->upscale,
            )
            : [null, null];

        return new AssetTransformResult(
            url: $url,
            mimeType: File::getMimeTypeByExtension("transform.{$format}") ?? "image/{$format}",
            width: $width,
            height: $height,
        );
    }

    public function withImmediateTransforms(callable $callback): mixed
    {
        return Context::scope(
            $callback,
            hidden: [self::IMMEDIATE_TRANSFORMS_CONTEXT => true],
        );
    }

    public function preloadAssetTransforms(array $requests): void
    {
        $groups = [];

        foreach ($requests as $request) {
            $key = serialize([$request->transformer->settings, $request->parameters]);
            $groups[$key]['transform'] ??= ImageTransform::fromParameters($request->parameters);
            $groups[$key]['transformer'] ??= $request->transformer;
            $groups[$key]['assets'][$request->asset->id] = $request->asset;
        }

        foreach ($groups as $group) {
            $this->imageTransformer->eagerLoadTransforms([$group['transform']], array_values($group['assets']), $group['transformer']);
        }
    }
}
