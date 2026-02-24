<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Exceptions\EntryTypeNotFoundException} instead.
     */
    class EntryTypeNotFoundException
    {
    }
}

class_alias(\CraftCms\Cms\Entry\Exceptions\EntryTypeNotFoundException::class, EntryTypeNotFoundException::class);
