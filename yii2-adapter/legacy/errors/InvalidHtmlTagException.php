<?php

namespace craft\errors;

use yii\base\InvalidArgumentException;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.7.27
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Exceptions\InvalidHtmlTagException} instead.
     */
    class InvalidHtmlTagException extends InvalidArgumentException
    {
    }
}

class_alias(\CraftCms\Cms\Support\Exceptions\InvalidHtmlTagException::class, InvalidHtmlTagException::class);
