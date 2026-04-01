<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string[] activeKeys()
 * @method static string start()
 * @method static void resume(string $key)
 * @method static void end(string $key)
 * @method static void trackElement(\craft\base\ElementInterface $element)
 * @method static mixed ensure(callable $callback)
 * @method static void defer(string $event, callable $handler, mixed $data = null, ?string $watchKey = null)
 *
 * @see \CraftCms\Cms\Element\BulkOp\BulkOps
 */
class BulkOps extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\BulkOp\BulkOps::class;
    }
}
