<?php

declare(strict_types=1);

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Exceptions\GqlException} instead.
     */
    class GqlException extends Exception
    {
    }
}

class_alias(\CraftCms\Cms\Gql\Exceptions\GqlException::class, GqlException::class);
