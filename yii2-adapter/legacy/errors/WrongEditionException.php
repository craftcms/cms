<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Edition\Exceptions\WrongEditionException} instead.
     */
    class WrongEditionException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Edition\Exceptions\WrongEditionException::class, WrongEditionException::class);
