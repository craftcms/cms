<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;
use Inertia\Support\Header;
use Override;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUserElement;

class HandleInertiaRequests extends Middleware
{
    #[Override]
    public function handle(Request $request, Closure $next)
    {
        $htmlStack = app(HtmlStack::class);

        app(InternalAssetRegistry::class)->register(CpAsset::class);
        View::composer('app', function ($view) use ($htmlStack) {
            $view->with([
                'headHtml' => $htmlStack->headHtml(),
                'bodyHtml' => $htmlStack->bodyHtml(),
            ]);
        });

        $response = parent::handle($request, $next);

        /*
         * Because we have both inertia and non-inertia pages we have a bit of
         * code in cp.ts that figures out when we need a full refresh or not.
         * That check relies on `x-redirect` which is usually set by Yii, but
         * sometimes we need to redirect from within laravel. This adds the
         * header so our cp.ts code still works.
         *
         * Once everything is inertia, this should be able to be removed.
         */
        if (
            $request->isMethod('GET') &&
            $request->inertia() &&
            ! $response->headers->has(Header::INERTIA) &&
            str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            $response->headers->set('X-Redirect', $request->fullUrl());
        }

        return $response;
    }

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    #[Override]
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    #[Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        $isInstalled = Cms::isInstalled();

        if (! $isInstalled) {
            return parent::share($request);
        }

        $currentSite = Sites::getCurrentSite();
        $updates = app(Updates::class);
        $nav = app(Navigation::class);
        $progressService = app(JobProgress::class);
        $currentUser = null;
        $generalConfig = app(GeneralConfig::class);

        if (! $updates->isCraftUpdatePending()) {
            $currentUser = currentUserElement();
        }

        $systemIcon = ($generalConfig->cpIconUrl && Edition::isAtLeast(Edition::Pro))
            ? Html::img(Aliases::get($generalConfig->cpIconUrl))->render()
            : Icons::svg('c-outline');

        return [
            ...parent::share($request),
            // Read through the Flash getters, which also cover the CP
            // notification keys that legacy-style controllers flash via
            // Flash::success()/error() without the plain session keys.
            'flash' => fn () => [
                'success' => Flash::getSuccess(),
                'error' => Flash::getError(),
            ],
            'queue' => fn () => Schema::hasTable(Table::JOBPROGRESS) ? [
                'displayedJob' => $progressService->getDisplayedJob(),
                'hasReservedJobs' => $progressService->getByStatus(JobStatus::Reserved)->count() > 0,
                'hasWaitingJobs' => $progressService->getByStatus(JobStatus::Pending)->count() > 0,
            ] : [
                'displayedJob' => null,
                'hasReservedJobs' => false,
                'hasWaitingJobs' => false,
            ],
            'isMultiSite' => fn () => Sites::isMultiSite(),
            'readOnly' => fn () => ! $generalConfig->allowAdminChanges,
            'locale' => fn () => app()->getLocale(),
            'craft' => fn () => [
                'csrfTokenValue' => csrf_token(),
                'csrfTokenName' => '_token',
                'general' => Cp::config()->toArray(),
                'system' => [
                    'name' => Cms::systemName(),
                    'icon' => $systemIcon,
                ],
                'app' => [
                    'version' => Cms::VERSION,
                    'edition' => Edition::get()->toArray(),
                ],
                'site' => $currentSite ? [
                    'id' => $currentSite->id,
                    'handle' => $currentSite->handle,
                    'url' => $currentSite->getBaseUrl(),
                ] : null,
                'currentUser' => $currentUser ? [
                    'id' => $currentUser->id,
                    'username' => $currentUser->username,
                    'email' => $currentUser->email,
                    'name' => $currentUser->name,
                    'thumbHtml' => $currentUser->getThumbHtml(30),
                ] : null,
                'readOnly' => ! $generalConfig->allowAdminChanges,
                'maintenanceMode' => app()->isDownForMaintenance(),
                'allowAdminChanges' => $generalConfig->allowAdminChanges,
                'baseCpUrl' => cp_url(),
                'actionUrl' => action_url(),
                'baseApiUrl' => Api::craftApiEndpoint(),
                'nav' => $nav->getItems(),
            ],
        ];
    }
}
