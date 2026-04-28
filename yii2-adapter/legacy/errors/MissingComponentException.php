<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * MissingComponentException represents an exception caused by creating a component with a missing class.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Exceptions\MissingComponentException} instead.
     */
    class MissingComponentException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Component\Exceptions\MissingComponentException::class, MissingComponentException::class);
