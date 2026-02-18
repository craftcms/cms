<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Deprecator\Models\DeprecationError;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void log(string $key, string $message, string|null $file = null, int|null $line = null)
 * @method static void storeLogs()
 * @method static array getRequestLogs()
 * @method static int getTotalLogs()
 * @method static array getLogs(int|null $limit = null)
 * @method static DeprecationError|null getLogById(int $logId)
 * @method static bool deleteLogById(int $id)
 * @method static bool deleteAllLogs()
 *
 * @see \CraftCms\Cms\Deprecator\Deprecator
 */
final class Deprecator extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Deprecator\Deprecator::class;
    }
}
