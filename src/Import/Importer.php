<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;

#[Singleton]
class Importer
{
    public function info(string $message, array $context = []): void
    {
        Log::channel('import')->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        Log::channel('import')->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        Log::channel('import')->error($message, $context);
    }
}
