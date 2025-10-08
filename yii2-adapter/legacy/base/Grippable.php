<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Contracts\Grippable} instead.
     */
    interface Grippable
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Contracts\Grippable::class, Grippable::class);
