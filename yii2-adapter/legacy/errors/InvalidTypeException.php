<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Exceptions\InvalidTypeException} instead.
     */
    class InvalidTypeException
    {
    }
}

class_alias(\CraftCms\Cms\Element\Exceptions\InvalidTypeException::class, InvalidTypeException::class);
