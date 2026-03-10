<?php

namespace craft\image;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Raster} instead.
     */
    class Raster extends \CraftCms\Cms\Image\Raster
    {
    }
}

class_alias(\CraftCms\Cms\Image\Raster::class, Raster::class);
