<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Gql\ApiController as GqlApiController;
use Illuminate\Foundation\Http\Middleware\PreventStaleDatabaseReads as BasePreventStaleDatabaseReads;
use Illuminate\Http\Request;

/**
 * Reads from the write connection on unsafe requests, and for a few seconds
 * after the same session wrote data.
 *
 * Laravel's middleware only covers requests that follow a write. Unsafe
 * requests also read from the write connection, so reads before and after
 * a write in the same request don't hit a replica that hasn't caught up.
 *
 * @since 6.0.0
 */
class PreventStaleDatabaseReads extends BasePreventStaleDatabaseReads
{
    private const array READ_ONLY_ACTIONS = [
        ElementIndexController::class.'@countElements',
        ElementIndexController::class.'@getElements',
        ElementIndexController::class.'@getMoreElements',
        ElementIndexSourcesController::class.'@getSourceTreeHtml',
        ExportElementIndexController::class,
        GqlApiController::class,
    ];

    /**
     * @param  Request  $request
     * @param  Closure(Request): mixed  $next
     * @param  int  $seconds
     */
    public function handle($request, Closure $next, $seconds = 2): mixed
    {
        return parent::handle($request, function (Request $request) use ($next) {
            $isReadOnlyAction = in_array($request->route()?->getAction('uses'), self::READ_ONLY_ACTIONS, true);

            if (! $request->isMethodSafe() && ! $isReadOnlyAction) {
                $this->manager->connection()->useWriteConnectionWhenReading();
            }

            return $next($request);
        }, $seconds);
    }
}
