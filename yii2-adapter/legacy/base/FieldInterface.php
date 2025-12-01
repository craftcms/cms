<?php

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Contracts\FieldInterface} instead.
     */
    interface FieldInterface extends \CraftCms\Cms\Field\Contracts\FieldInterface, ModelInterface
    {
    }
}

class_alias(\CraftCms\Cms\Field\Contracts\FieldInterface::class, FieldInterface::class);
