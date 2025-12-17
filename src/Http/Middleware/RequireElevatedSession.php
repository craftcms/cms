<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

final readonly class RequireElevatedSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        Craft::$app->getUser()->setIdentity(
            new IdentityWrapper($request->user()),
        );

        abort_unless(
            Craft::$app->getUser()->getHasElevatedSession(),
            403,
            t('This action may only be performed with an elevated session.')
        );

        return $next($request);
    }
}
