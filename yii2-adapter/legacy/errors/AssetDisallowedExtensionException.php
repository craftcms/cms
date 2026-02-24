<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException} instead.
     */
    class AssetDisallowedExtensionException
    {
    }
}

class_alias(\CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException::class, AssetDisallowedExtensionException::class);
