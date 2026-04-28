<?php

namespace craft\imagetransforms;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * FallbackTransformer transforms image assets using GD or ImageMagick, and stores them in the storage folder.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\FallbackTransformer} instead.
     */
    class FallbackTransformer
    {
    }
}

class_alias(\CraftCms\Cms\Image\FallbackTransformer::class, FallbackTransformer::class);
