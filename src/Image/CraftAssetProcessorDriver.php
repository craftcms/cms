<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Exceptions\ImageTransformException;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\File;

use function CraftCms\Cms\t;

class CraftAssetProcessorDriver implements AssetProcessorDriver, PreloadsAssetTransforms
{
    public function __construct(private readonly ImageTransformer $imageTransformer) {}

    public function definition(): AssetProcessorDriverDefinition
    {
        return new AssetProcessorDriverDefinition(t('Craft'), settings: [
            Field::make(t('Output Filesystem'), Combobox::make('filesystem')
                ->value(null)
                ->options([
                    ['label' => t('Same as source'), 'value' => ''],
                    ...SelectOptions::getFsOptions(),
                    ...SelectOptions::getEnvSuggestions(),
                ])),
            Field::make(t('Output Subpath'), Combobox::make('subpath')
                ->value('')
                ->options(SelectOptions::getEnvSuggestions(true))),
        ]);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        if (! ImageHelper::canManipulateAsImage($request->asset->getExtension())) {
            throw new NotSupportedException('The Asset cannot be manipulated as an image.');
        }

        $transform = new ImageTransform($request->operations);
        try {
            $url = $this->imageTransformer->getTransformUrl($request->asset, $transform, $request->immediately, $request->processor);
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

    public function preloadAssetTransforms(array $requests): void
    {
        $groups = [];

        foreach ($requests as $request) {
            $key = $request->processor->uid.':'.serialize($request->operations);
            $groups[$key]['transform'] ??= new ImageTransform($request->operations);
            $groups[$key]['processor'] ??= $request->processor;
            $groups[$key]['assets'][$request->asset->id] = $request->asset;
        }

        foreach ($groups as $group) {
            $this->imageTransformer->eagerLoadTransforms([$group['transform']], array_values($group['assets']), $group['processor']);
        }
    }
}
