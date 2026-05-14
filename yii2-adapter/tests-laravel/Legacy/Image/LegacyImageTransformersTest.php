<?php

use craft\base\imagetransforms\ImageTransformerInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\services\ImageTransforms as LegacyImageTransforms;
use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;

it('keeps legacy image transformer registration on the adapter service', function() {
    $customTransformer = (new class() implements ImageTransformerInterface {
        public function __construct(array $config = [])
        {
        }

        public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
        {
            return 'https://example.test/legacy-transform';
        }

        public function invalidateAssetTransforms(Asset $asset): void
        {
        }
    })::class;

    Craft::$app->getImageTransforms()->on(
        LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS,
        function(RegisterComponentTypesEvent $event) use ($customTransformer) {
            $event->types[] = $customTransformer;
        },
    );

    expect(Craft::$app->getImageTransforms()->getAllImageTransformers())
        ->toContain($customTransformer);
});

it('bridges legacy image transformers into the asset transformer registry', function() {
    $customTransformer = (new class() implements ImageTransformerInterface {
        public function __construct(array $config = [])
        {
        }

        public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
        {
            return 'https://example.test/legacy-transform';
        }

        public function invalidateAssetTransforms(Asset $asset): void
        {
        }
    })::class;

    Craft::$app->getImageTransforms()->on(
        LegacyImageTransforms::EVENT_REGISTER_IMAGE_TRANSFORMERS,
        function(RegisterComponentTypesEvent $event) use ($customTransformer) {
            $event->types[] = $customTransformer;
        },
    );

    $transformers = app(AssetTransforms::class)->getAllAssetTransformers();

    expect($transformers)->toHaveKey($customTransformer)
        ->and(app(AssetTransforms::class)->getAssetTransformer($customTransformer))
        ->toBeInstanceOf(AssetTransformerInterface::class);
});
