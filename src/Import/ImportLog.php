<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;

#[Singleton]
class ImportLog
{
    /**
     * Logs an info-level message to the `import` log channel.
     *
     * @param  string  $message  The message to log.
     * @param  array  $context  Additional context data for the log entry.
     */
    public function info(string $message, array $context = []): void
    {
        Log::channel('import')->info($message, $context);
    }

    /**
     * Logs a warning-level message to the `import` log channel.
     *
     * @param  string  $message  The message to log.
     * @param  array  $context  Additional context data for the log entry.
     */
    public function warning(string $message, array $context = []): void
    {
        Log::channel('import')->warning($message, $context);
    }

    /**
     * Logs an error-level message to the `import` log channel.
     *
     * @param  string  $message  The message to log.
     * @param  array  $context  Additional context data for the log entry.
     */
    public function error(string $message, array $context = []): void
    {
        Log::channel('import')->error($message, $context);
    }
}
