<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Database\Exceptions\MigrateException} instead.
     */
    class MigrateException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Database\Exceptions\MigrateException::class, MigrateException::class);
