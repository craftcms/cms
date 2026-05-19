<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;
use Override;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\action_url;
use function CraftCms\Cms\cp_url;

class HandleInertiaRequests extends Middleware
{
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

    #[Override]
    public function handle(Request $request, Closure $next): Response
    {
        app(InternalAssetRegistry::class)->register(CpAsset::class);
        $htmlStack = app(HtmlStack::class);

        View::composer('app', function ($view) use ($htmlStack) {
            $view->with([
                'headHtml' => $htmlStack->headHtml(),
                'bodyHtml' => $htmlStack->bodyHtml(),
            ]);
        });

        return parent::handle($request, $next);
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
        $generalConfig = app(GeneralConfig::class);
        $currentUser = null;

        if (! $updates->isCraftUpdatePending()) {
            $currentUser = $request->user();
        }

        $systemIcon = ($generalConfig->cpIconUrl && Edition::isAtLeast(Edition::Pro))
            ? Html::img(Aliases::get($generalConfig->cpIconUrl))->render()
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
            'isMultiSite' => fn () => Sites::isMultiSite(),
            'readOnly' => fn () => ! $generalConfig->allowAdminChanges,
            'locale' => fn () => app()->getLocale(),
            'craft' => fn () => [
                'csrfTokenValue' => csrf_token(),
                'csrfTokenName' => '_token',
                'general' => [
                    'useEmailAsUsername' => $generalConfig->useEmailAsUsername,
                ],
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
                'currentUser' => [
                    'id' => $currentUser->id ?? null,
                    'username' => $currentUser->username ?? null,
                    'email' => $currentUser->email ?? null,
                    'name' => $currentUser->name ?? null,
                    'thumbHtml' => $currentUser?->getThumbHtml(30),
                ],
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
