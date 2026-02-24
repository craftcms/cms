<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidLicenseKeyException extends RuntimeException
{
    public function __construct(
        public readonly string $licenseKey,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message ?: "The license key “{$licenseKey}” is invalid.", $code, $previous);
    }
}
