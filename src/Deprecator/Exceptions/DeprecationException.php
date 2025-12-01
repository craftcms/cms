<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator\Exceptions;

use Exception;
use Throwable;

final class DeprecationException extends Exception
{
    public function __construct(string $message = '', ?string $file = null, ?int $line = null, int $code = 0, ?Throwable $previous = null)
    {
        if ($file !== null) {
            $this->file = $file;
        }

        if ($line !== null) {
            $this->line = $line;
        }

        parent::__construct($message, $code, $previous);
    }
}
