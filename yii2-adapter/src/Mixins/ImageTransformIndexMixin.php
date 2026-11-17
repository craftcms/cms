<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use craft\imagetransforms\ImageTransformer;

class ImageTransformIndexMixin
{
    /**
     * Craft 5’s `ImageTransformIndex::$transformer` held the transformer class. Every index row now belongs to Craft’s
     * own transformer.
     */
    public function getTransformer(): Closure
    {
        return fn(): string => ImageTransformer::class;
    }
}
