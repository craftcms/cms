<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers image transformer classes available to Craft.
 *
 * ```php
 * public function boot(ImageTransformers $imageTransformers): void
 * {
 *     $imageTransformers->register(MyImageTransformer::class);
 * }
 * ```
 *
 * @extends TypeRegistry<ImageTransformerInterface>
 */
#[Singleton]
class ImageTransformers extends TypeRegistry
{
    protected const string CONTRACT = ImageTransformerInterface::class;

    protected const array DEFAULT_TYPES = [ImageTransformer::class];
}
