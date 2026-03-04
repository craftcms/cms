<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Exceptions;

use RuntimeException;

final class CommandFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $command,
        public readonly int $exitCode,
        public readonly ?string $error = null,
    ) {
        parent::__construct(
            sprintf(
                'The shell command "%s" failed with exit code %d%s',
                $command,
                $exitCode,
                $error ? ": $error" : '.',
            )
        );
    }
}
