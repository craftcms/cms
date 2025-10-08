<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Contracts\CpEditable} instead.
     */
    interface CpEditable
    {
    }
}

class_alias(\CraftCms\Cms\Shared\Contracts\CpEditable::class, CpEditable::class);
