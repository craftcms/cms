<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.7.35
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException} instead.
     */
    class BusyResourceException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException::class, BusyResourceException::class);
