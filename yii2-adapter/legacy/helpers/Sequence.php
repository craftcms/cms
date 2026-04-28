<?php

namespace craft\helpers;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.31
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Sequence} instead.
     */
    class Sequence
    {
    }
}

class_alias(\CraftCms\Cms\Support\Sequence::class, Sequence::class);
