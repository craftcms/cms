<?php

namespace craft\elements\db;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface} instead.
     */
    interface ElementQueryInterface
    {
    }
}

class_alias(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface::class, ElementQueryInterface::class);
