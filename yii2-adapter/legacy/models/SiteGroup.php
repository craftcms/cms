<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Data\SiteGroup} instead.
     */
    class SiteGroup
    {
    }
}

class_alias(\CraftCms\Cms\Site\Data\SiteGroup::class, SiteGroup::class);
