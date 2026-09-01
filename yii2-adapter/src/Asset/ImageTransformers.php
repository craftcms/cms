<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use craft\base\imagetransforms\ImageTransformerInterface;
use craft\imagetransforms\ImageTransformer;
use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;

/** @extends TypeRegistry<ImageTransformerInterface> */
#[Singleton]
class ImageTransformers extends TypeRegistry
{
    protected const string CONTRACT = ImageTransformerInterface::class;

    protected const array DEFAULT_TYPES = [ImageTransformer::class];
}
