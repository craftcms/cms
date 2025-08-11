<?php

namespace craft\enums;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0. Use {@see \CraftCms\Cms\CmsEdition} instead.
     */
    enum CmsEdition: int
    {
    }
}

class_alias(\CraftCms\Cms\CmsEdition::class, CmsEdition::class);
