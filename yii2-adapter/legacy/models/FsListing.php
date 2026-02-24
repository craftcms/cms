<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Data\FsListing} instead.
     */
    class FsListing
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Data\FsListing::class, FsListing::class);
