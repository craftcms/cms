<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\ConnectionConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class CheckRequirements
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        /** Don't verify requirements for action update requests. */
        if (str_contains($request->path(), $this->generalConfig->actionTrigger.'/updater')) {
            return $next($request);
        }

        /** Don't verify requirements for action migrate requests. */
        if (str_contains($request->path(), 'app/migrate')) {
            return $next($request);
        }

        // Keyed by base path, so a moved install is checked again, and installs at different paths that share a cache,
        // such as during a rolling deploy, don't overwrite each other's result
        $cacheKey = sprintf('requirementsChecked:%s', md5(base_path()));

        if (Cache::has($cacheKey)) {
            return $next($request);
        }

        $reqCheck = ConnectionConfig::requirementsChecker();

        $reqCheck->checkCraft();

        if ($reqCheck->result['summary']['errors'] === 0) {
            // A `cacheDuration` of 0 caches it indefinitely
            Cache::put($cacheKey, true, $this->generalConfig->cacheDuration ?: null);

            return $next($request);
        }

        // Coming from Updater.php
        if ($request->wantsJson()) {
            $message = '<br /><br />';

            foreach ($reqCheck->getResult()['requirements'] as $req) {
                if ($req['error'] === true) {
                    $message .= $req['memo'].'<br />';
                }
            }

            throw new RuntimeException(t('The update can’t be installed :( {message}', ['message' => $message]));
        }

        return view('_special/cantrun', [
            'reqCheck' => $reqCheck,
        ]);
    }
}
