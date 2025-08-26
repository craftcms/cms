<?php

namespace craft\enums;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0. use {@see \CraftCms\Cms\Shared\Enums\Color} instead.
     */
    enum Color: string
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Enums\Color::class, Color::class);
