<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Craft;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\CP\Navigation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'c::app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    #[\Override]
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
    #[\Override]
    public function share(Request $request): array
    {
        $sitesService = Craft::$app->getSites();
        $currentSite = $sitesService->getCurrentSite();
        $isInstalled = Craft::$app->getIsInstalled();
        $updates = app(Updates::class);

        if ($isInstalled && ! $updates->isCraftUpdatePending()) {
            $currentUser = Craft::$app->getUser()->getIdentity();

            if (! $currentUser) {
                $user = Auth::user();

                if ($user) {
                    Craft::$app->getUser()->setIdentity(Craft::$app->getUsers()->getUserById($user->id));
                    $currentUser = Craft::$app->getUser()->getIdentity();
                }
            }
        }

        $systemIcon = Cp::iconSvg('c-outline');

        if (Edition::get()->value >= Edition::Pro->value && $rebrand = app(Rebrand::class)) {
            $systemIcon = $rebrand->isIconUploaded() ? $rebrand->getIcon()->getUrl() : $systemIcon;
        }

        return [
            ...parent::share($request),
            'craft' => [
                'system' => [
                    'name' => Craft::$app->getSystemName(),
                    'icon' => $systemIcon,
                ],
                'app' => [
                    'version' => Craft::$app->getVersion(),
                    'edition' => Edition::get()->name,
                ],
                'site' => [
                    'url' => $currentSite->getBaseUrl(),
                ],
                'currentUser' => [
                    'email' => $currentUser->email ?? null,
                ],
                'cpUrl' => UrlHelper::cpUrl(),
                'actionUrl' => UrlHelper::actionUrl(),
                'nav' => Navigation::getItems(),
            ],
        ];
    }
}
