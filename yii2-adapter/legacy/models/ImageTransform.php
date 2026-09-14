<?php

namespace craft\models;

use Craft;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\imagetransforms\ImageTransformer;

/** @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Data\ImageTransform} instead. */
class ImageTransform extends \CraftCms\Cms\Image\Data\ImageTransform
{
    public const string DEFAULT_TRANSFORMER = ImageTransformer::class;

    /** @var class-string<ImageTransformerInterface> */
    protected string $transformer = self::DEFAULT_TRANSFORMER;

    public function getIsNamedTransform(): bool
    {
        return (bool) $this->id && $this->transformer === self::DEFAULT_TRANSFORMER;
    }

    /** @return class-string<ImageTransformerInterface> */
    public function getTransformer(): string
    {
        return $this->transformer;
    }

    /** @param class-string<ImageTransformerInterface>|null $transformer */
    public function setTransformer(?string $transformer): void
    {
        $this->transformer = $transformer ?? self::DEFAULT_TRANSFORMER;
    }

    public function getImageTransformer(): ImageTransformerInterface
    {
        return Craft::$app->getImageTransforms()->getImageTransformer($this->transformer);
    }
}
