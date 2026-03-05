<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Image} instead.
     */
    abstract class Image extends \CraftCms\Cms\Image\Image
    {
    }
}

class_alias(\CraftCms\Cms\Image\Image::class, Image::class);
