<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Data\Site} instead.
     */
    class Site
    {
    }
}

class_alias(\CraftCms\Cms\Site\Data\Site::class, Site::class);
