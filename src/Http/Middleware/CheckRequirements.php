<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RequirementsChecker;
use RuntimeException;

use function CraftCms\Cms\t;

final readonly class CheckRequirements
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

        $cachedBasePath = Cache::get('basePath');

        if ($cachedBasePath || $cachedBasePath === base_path()) {
            return $next($request);
        }

        $reqCheck = new RequirementsChecker;
        $reqCheck->dbDriver = DB::connection()->getDriverName();

        $prefix = "database.connections.{$reqCheck->dbDriver}";
        $reqCheck->dsn = implode('', [
            $reqCheck->dbDriver,
            ':host='.Config::get("{$prefix}.host"),
            ';port='.Config::get("{$prefix}.port"),
            ';dbname='.Config::get("{$prefix}.database"),
            ';user='.Config::get("{$prefix}.username"),
            ';password='.Config::get("{$prefix}.password"),
        ]);
        $reqCheck->dbUser = Config::get("{$prefix}.username");
        $reqCheck->dbPassword = Config::get("{$prefix}.password");

        $reqCheck->checkCraft();

        if ($reqCheck->result['summary']['errors'] === 0) {
            // Cache the base path.
            Cache::put('basePath', base_path(), $this->generalConfig->cacheDuration);

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
