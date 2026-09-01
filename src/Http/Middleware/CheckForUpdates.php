<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use function CraftCms\Cms\t;

readonly class CheckForUpdates
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Updates $updates,
        private HandleActionRequest $handleActionRequest,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->ensureSchemaVersionIsCompatible($request);

        if (! Cms::isInstalled()) {
            return $next($request);
        }

        if ($this->updates->isCraftUpdatePending()) {
            return $this->processUpdate($request, $next);
        }

        if ($this->updates->hasCraftVersionChanged()) {
            $this->updates->updateCraftVersionInfo();

            if (! File::cleanDirectory(Path::compiledTemplates(create: false))) {
                Log::error('Could not delete compiled templates');
            }
        }

        if ($this->updates->isPluginUpdatePending()) {
            return $this->processUpdate($request, $next);
        }

        return $next($request);
    }

    private function ensureSchemaVersionIsCompatible(Request $request): void
    {
        if ($this->updates->isCraftSchemaVersionCompatible()) {
            return;
        }

        if ($request->is($this->generalConfig->cpTrigger.'*')) {
            $version = Info::fetch()->version;

            throw new RuntimeException(t('Craft CMS does not support backtracking to this version. Please update to Craft CMS {version} or later.', [
                'version' => $version,
            ]));
        }

        abort(503);
    }

    private function processUpdate(Request $request, Closure $next): mixed
    {
        if ($request->routeIs('craft.cp.updates.*')) {
            return $next($request);
        }

        if ($request->isCpRequest() && (! $request->isActionRequest() || str_contains($request->path(), 'users/login'))) {
            if ($this->updates->wasCraftBreakpointSkipped()) {
                throw new RuntimeException(t('You need to be on at least Craft CMS {version} before you can manually update to Craft CMS {targetVersion}.', [
                    'version' => Cms::MIN_VERSION_REQUIRED,
                    'targetVersion' => Cms::VERSION,
                ]));
            }

            File::cleanDirectory(Path::compiledTemplates(create: false));

            TemplateMode::set(TemplateMode::Cp);

            return response()->view('_special/dbupdate');
        }

        if ($request->isActionRequest()) {
            $actionSegments = $request->actionSegments();

            if (
                $actionSegments === ['app', 'health-check'] ||
                $actionSegments === ['migrate'] ||
                $actionSegments === ['pluginstore', 'install', 'migrate']
            ) {
                return $this->handleActionRequest->handle($request, $next);
            }
        }

        abort(503);
    }
}
