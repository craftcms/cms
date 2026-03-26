<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use craft\web\twig\variables\CraftVariable;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Update\Updates;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;
use Throwable;

use function CraftCms\Cms\t;

#[Scoped]
readonly class TemplateGlobals
{
    public function __construct(
        private Application $app,
        private GeneralConfig $generalConfig,
        private Plugins $plugins,
        private Request $request,
        private Sites $sites,
        private Updates $updates,
    ) {}

    /**
     * Resolve the shared template globals available to both Twig and Blade.
     *
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        $isInstalled = Cms::isInstalled();
        $setPasswordRequestPath = $this->generalConfig->getSetPasswordRequestPath();

        if ($isInstalled && ! $this->updates->isCraftUpdatePending()) {
            $currentSite = $this->sites->getCurrentSite();
            $primarySite = $this->sites->getPrimarySite();
            $currentUser = Auth::user();
            $siteName = t($currentSite->getName(), category: 'site');
            $siteUrl = $currentSite->getBaseUrl();
            $systemName = Cms::systemName();
        } else {
            $currentSite = $primarySite = $currentUser = $siteName = $siteUrl = $systemName = null;
        }

        try {
            $errors = $this->request->session()->get('errors') ?: new ViewErrorBag;
        } catch (Throwable) {
            // Session is not started yet
            $errors = new ViewErrorBag;
        }

        return [
            'craft' => new CraftVariable,
            'sessionErrors' => $errors,
            'request' => $this->request,
            'pluginAssets' => $this->plugins->getAssetsHtml(),
            'currentSite' => $currentSite,
            'currentUser' => $currentUser,
            'primarySite' => $primarySite,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'systemName' => $systemName,
            'devMode' => $this->app->hasDebugModeEnabled(),
            'isInstalled' => $isInstalled,
            'isUpdateInfoCached' => $this->updates->isUpdateInfoCached(),
            'loginUrl' => Url::siteUrl($this->generalConfig->getLoginPath()),
            'logoutUrl' => Url::siteUrl($this->generalConfig->getLogoutPath()),
            'setPasswordUrl' => $setPasswordRequestPath !== null ? Url::siteUrl($setPasswordRequestPath) : null,
            'now' => now(),
            'today' => today(),
            'tomorrow' => today()->addDay(),
            'yesterday' => today()->subDay(),
        ];
    }
}
