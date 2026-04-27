<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exceptions;

use RuntimeException;
use Throwable;

class InvalidTypeException extends RuntimeException
{
    public function __construct(
        public readonly string $class,
        public readonly string $requiredType,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: "{$class} doesn't exist or doesn't extend/implement {$requiredType}", $code, $previous);
    }
}
