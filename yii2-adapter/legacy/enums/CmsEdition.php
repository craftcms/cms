<?php

namespace craft\enums;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0. Use {@see \CraftCms\Cms\Edition} instead.
     */
    enum Edition: int
    {
    }
}

class_alias(\CraftCms\Cms\Edition::class, Edition::class);
