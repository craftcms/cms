<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Exceptions;

use RuntimeException;
use Throwable;

class InvalidFieldException extends RuntimeException
{
    public function __construct(
        public readonly string $handle,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: "Invalid field handle: {$handle}", $code, $previous);
    }
}
