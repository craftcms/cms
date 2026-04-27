<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\Events\RegisterTemplateGlobals;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Foundation\Application;

use function CraftCms\Cms\t;

#[Scoped]
readonly class TemplateGlobals
{
    public function __construct(
        private Application $app,
        private GeneralConfig $generalConfig,
        private Sites $sites,
        private Updates $updates,
        private CraftVariable $craftVariable,
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
            $siteName = t($currentSite->getName(), category: 'site');
            $siteUrl = $currentSite->getBaseUrl();
            $systemName = Cms::systemName();
        } else {
            $siteName = $siteUrl = $systemName = null;
        }

        $globals = [
            'craft' => $this->craftVariable,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'systemName' => $systemName,
            'language' => app()->getLocale(),
            'devMode' => $this->app->hasDebugModeEnabled(),
            'isInstalled' => $isInstalled,
            'loginUrl' => Url::siteUrl($this->generalConfig->getLoginPath()),
            'logoutUrl' => Url::siteUrl($this->generalConfig->getLogoutPath()),
            'setPasswordUrl' => $setPasswordRequestPath !== null ? Url::siteUrl($setPasswordRequestPath) : null,
            'now' => now(),
            'today' => today(),
            'tomorrow' => today()->addDay(),
            'yesterday' => today()->subDay(),
        ];

        event($event = new RegisterTemplateGlobals($globals));

        return $event->globals;
    }
}
