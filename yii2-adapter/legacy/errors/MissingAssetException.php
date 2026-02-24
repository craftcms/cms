<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.2.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Exceptions\MissingAssetException} instead.
     */
    class MissingAssetException
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Exceptions\MissingAssetException::class, MissingAssetException::class);
