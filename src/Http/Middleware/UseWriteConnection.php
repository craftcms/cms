<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Gql\ApiController as GqlApiController;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;

readonly class UseWriteConnection
{
    private const array READ_ONLY_ACTIONS = [
        ElementIndexController::class.'@countElements',
        ElementIndexController::class.'@getElements',
        ElementIndexController::class.'@getMoreElements',
        ElementIndexSourcesController::class.'@getSourceTreeHtml',
        ExportElementIndexController::class,
        GqlApiController::class,
    ];

    public function __construct(
        private Connection $db,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $isReadOnlyAction = in_array($request->route()?->getAction('uses'), self::READ_ONLY_ACTIONS, true);

        $this->db->useWriteConnectionWhenReading(
            ! $request->isMethodSafe() &&
            ! $isReadOnlyAction,
        );

        return $next($request);
    }
}
