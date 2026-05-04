<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

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
use CraftCms\Cms\Update\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;
use Override;

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
        $generalConfig = app(GeneralConfig::class);
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
                    'thumbHtml' => $currentUser->getThumbHtml(30),
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
