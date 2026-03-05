<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.5.12
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Exceptions\InvalidFieldException} instead.
     */
    class InvalidFieldException
    {
    }
}

class_alias(\CraftCms\Cms\Field\Exceptions\InvalidFieldException::class, InvalidFieldException::class);
