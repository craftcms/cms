<?php

namespace craft\base;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface} instead.
     */
    interface ElementContainerFieldInterface extends FieldInterface
    {
    }
}

class_alias(\CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface::class, ElementContainerFieldInterface::class);
