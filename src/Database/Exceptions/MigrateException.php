<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Exceptions;

use Exception;
use Throwable;

class MigrateException extends Exception
{
    public function __construct(
        public string $ownerName,
        public string $ownerHandle,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if ($message === null) {
            $message = 'An error occurred while migrating '.$ownerName.'.';
        }

        parent::__construct($message, $code, $previous);
    }
}
