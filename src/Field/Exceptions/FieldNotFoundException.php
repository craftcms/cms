<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Exceptions;

use Exception;
use Throwable;

class FieldNotFoundException extends Exception
{
    public function __construct(
        public string $fieldUid,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
