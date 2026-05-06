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
use CraftCms\Cms\Update\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Inertia\Support\Header;
use Override;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;

class HandleInertiaRequests extends Middleware
{
    #[Override]
    public function handle(Request $request, Closure $next)
    {
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
        $generalConfig = app(GeneralConfig::class);
        $updates = app(Updates::class);
        $nav = app(Navigation::class);
        $progressService = app(JobProgress::class);
        $currentUser = null;

        if (! $updates->isCraftUpdatePending()) {
            $currentUser = $request->user();
        }

        $systemIcon = Cms::config()->cpIconUrl
            ? Aliases::get(Cms::config()->cpIconUrl)
            : Icons::svg('c-outline');

        return [
            ...parent::share($request),
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
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
            'craft' => fn () => [
                'csrfTokenValue' => $generalConfig->enableCsrfProtection ? csrf_token() : null,
                'csrfTokenName' => $generalConfig->enableCsrfProtection ? $generalConfig->csrfTokenName : null,
                'general' => Cp::config()->toArray(),
                'system' => [
                    'name' => Cms::systemName(),
                    'icon' => $systemIcon,
                ],
                'app' => [
                    'version' => Cms::VERSION,
                    'edition' => Edition::get()->toArray(),
                ],
                'site' => [
                    'url' => $currentSite->getBaseUrl(),
                ],
                'currentUser' => $currentUser ? [
                    'id' => $currentUser->id,
                    'username' => $currentUser->username,
                    'email' => $currentUser->email,
                    'name' => $currentUser->name,
                    'thumbHtml' => $currentUser->getThumbHtml(30),
                ] : null,
                'readOnly' => ! $generalConfig->allowAdminChanges,
                'allowAdminChanges' => $generalConfig->allowAdminChanges,
                'cpUrl' => cp_url(),
                'actionUrl' => action_url(),
                'baseApiUrl' => Api::craftApiEndpoint(),
                'nav' => $nav->getItems(),
            ],
        ];
    }
}
