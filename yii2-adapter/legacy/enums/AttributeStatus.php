<?php

namespace craft\enums;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0. Use {@see \CraftCms\Cms\Element\Enums\AttributeStatus} instead.
     */
    enum AttributeStatus: string
    {
        case Modified = 'modified';
        case Outdated = 'outdated';
    }
}

class_alias(\CraftCms\Cms\Element\Enums\AttributeStatus::class, AttributeStatus::class);
