<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Exceptions;

use Exception;
use Throwable;

class InvalidConfigException extends Exception
{
    public function __construct(
        public string $config,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
