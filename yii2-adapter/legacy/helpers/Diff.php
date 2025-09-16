<?php

namespace craft\helpers;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.6.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Diff} instead.
     */
    class Diff
    {
    }
}

class_alias(\CraftCms\Cms\Support\Diff::class, Diff::class);
