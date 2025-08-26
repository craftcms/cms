<?php

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Shared\Exceptions\OperationAbortedException} instead.
     */
    class OperationAbortedException extends Exception
    {
        /**
         * @return string the user-friendly name of this exception
         */
        public function getName(): string
        {
            return 'Operation aborted';
        }
    }
}

class_alias(\CraftCms\Cms\Shared\Exceptions\OperationAbortedException::class, OperationAbortedException::class);
