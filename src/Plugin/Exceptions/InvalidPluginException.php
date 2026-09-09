<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Exceptions;

use Exception;

class InvalidPluginException extends Exception
{
    public function __construct(
        public string $handle,
        ?string $message = null,
        int $code = 0,
    ) {
        $message ??= "No plugin exists with the handle \"$handle\".";

        parent::__construct($message, $code);
    }
}
