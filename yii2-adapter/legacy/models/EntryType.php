<?php

namespace craft\models;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Data\EntryType} instead.
     */
    class EntryType
    {
    }
}

class_alias(\CraftCms\Cms\Entry\Data\EntryType::class, EntryType::class);
