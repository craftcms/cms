<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Shared\Concerns\FetchesInfo;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\Request;
use RuntimeException;

final class CheckSchemaVersion
{
    use FetchesInfo;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Updates $updates,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->updates->isCraftSchemaVersionCompatible()) {
            return $next($request);
        }

        if ($request->is($this->generalConfig->cpTrigger.'*')) {
            $version = $this->fetchInfo()->version;

            throw new RuntimeException(Craft::t('app', 'Craft CMS does not support backtracking to this version. Please update to Craft CMS {version} or later.', [
                'version' => $version,
            ]));
        }

        abort(503);
    }
}
