<?php

namespace craft\models;

use CraftCms\Cms\Section\Data\SectionSiteSettings;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Section\Data\SectionSiteSettings} instead.
     */
    class Section_SiteSettings
    {
    }
}

class_alias(SectionSiteSettings::class, Section_SiteSettings::class);
