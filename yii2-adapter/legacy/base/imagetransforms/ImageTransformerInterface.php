<?php

namespace craft\base\imagetransforms;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Contracts\ImageTransformerInterface} instead.
     */
    interface ImageTransformerInterface extends \CraftCms\Cms\Image\Contracts\ImageTransformerInterface
    {
    }
}

class_alias(\CraftCms\Cms\Image\Contracts\ImageTransformerInterface::class, ImageTransformerInterface::class);
