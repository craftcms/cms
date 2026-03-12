<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Exceptions;

use RuntimeException;
use Throwable;

class InvalidSubpathException extends RuntimeException
{
    public function __construct(
        public readonly string $subpath,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: "Could not resolve the subpath “{$subpath}”.", $code, $previous);
    }
}
