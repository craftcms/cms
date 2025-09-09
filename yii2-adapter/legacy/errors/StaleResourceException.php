<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException} instead.
     */
    class StaleResourceException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException::class, StaleResourceException::class);
