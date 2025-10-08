<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Contracts\Chippable} instead.
     */
    interface Chippable extends \CraftCms\Cms\Shared\Contracts\Identifiable
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Contracts\Chippable::class, Chippable::class);
