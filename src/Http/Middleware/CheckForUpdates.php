<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use function CraftCms\Cms\t;

final readonly class CheckForUpdates
{
    public function __construct(
        private Updates $updates,
        private HandleActionRequest $handleActionRequest,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! Cms::isInstalled()) {
            return $next($request);
        }

        if ($this->updates->isCraftUpdatePending()) {
            return $this->processUpdate($request, $next);
        }

        if ($this->updates->hasCraftVersionChanged()) {
            $this->updates->updateCraftVersionInfo();

            if (! File::cleanDirectory(Craft::$app->getPath()->getCompiledTemplatesPath(false))) {
                Log::error('Could not delete compiled templates');
            }
        }

        if ($this->updates->isPluginUpdatePending()) {
            return $this->processUpdate($request, $next);
        }

        return $next($request);
    }

    private function processUpdate(Request $request, Closure $next): mixed
    {
        if ($request->isCpRequest() && (! $request->isActionRequest() || str_contains($request->path(), 'users/login'))) {
            if ($this->updates->wasCraftBreakpointSkipped()) {
                throw new RuntimeException(t('You need to be on at least Craft CMS {version} before you can manually update to Craft CMS {targetVersion}.', [
                    'version' => Cms::MIN_VERSION_REQUIRED,
                    'targetVersion' => Cms::VERSION,
                ]));
            }

            File::cleanDirectory(Craft::$app->getPath()->getCompiledTemplatesPath(false));

            return response(Craft::$app->getView()->renderPageTemplate('_special/dbupdate.twig'));
        }

        if ($request->isActionRequest()) {
            $actionSegments = $request->actionSegments();

            if (
                Arr::first($actionSegments) === 'updater' ||
                $actionSegments === ['app', 'health-check'] ||
                $actionSegments === ['app', 'migrate'] ||
                $actionSegments === ['pluginstore', 'install', 'migrate']
            ) {
                return $this->handleActionRequest->handle($request, $next);
            }
        }

        abort(503);
    }
}
