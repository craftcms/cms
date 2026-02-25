<?php

namespace craft\base\imagetransforms;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Image\Contracts\ImageEditorTransformerInterface} instead.
     */
    interface ImageEditorTransformerInterface extends \CraftCms\Cms\Image\Contracts\ImageEditorTransformerInterface
    {
    }
}

class_alias(\CraftCms\Cms\Image\Contracts\ImageEditorTransformerInterface::class, ImageEditorTransformerInterface::class);
