<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Contracts\Iconic} instead.
     */
    interface Iconic
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\Iconic::class, Iconic::class);
