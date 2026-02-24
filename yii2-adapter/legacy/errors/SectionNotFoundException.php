<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Section\Exceptions\SectionNotFoundException} instead.
     */
    class SectionNotFoundException
    {
    }
}

class_alias(\CraftCms\Cms\Section\Exceptions\SectionNotFoundException::class, SectionNotFoundException::class);
