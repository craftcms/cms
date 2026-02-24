<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Exceptions\ImageException} instead.
     */
    class ImageException
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Exceptions\ImageException::class, ImageException::class);
