<?php

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException} instead.
     */
    class InvalidSubpathException
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException::class, InvalidSubpathException::class);
