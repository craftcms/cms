<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.1.18
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Deprecator\Exceptions\DeprecationException} instead.
     */
    class DeprecationException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Deprecator\Exceptions\DeprecationException::class, DeprecationException::class);
