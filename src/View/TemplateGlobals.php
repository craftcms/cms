<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Variables\CraftVariable;
use CraftCms\Cms\Update\Updates;
use CraftCms\Cms\View\Events\TemplateGlobalsResolving;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Foundation\Application;

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

#[Scoped]
class TemplateGlobals
{
    /** @var array<string, mixed>|null */
    private ?array $globals = null;

    public function __construct(
        private readonly Application $app,
        private readonly GeneralConfig $generalConfig,
        private readonly Sites $sites,
        private readonly Updates $updates,
        private readonly CraftVariable $craftVariable,
    ) {}

    /**
     * Resolve the shared template globals available to both Twig and Blade.
     *
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        if (isset($this->globals)) {
            return $this->globals;
        }

        $isInstalled = Cms::isInstalled();
        $setPasswordRequestPath = $this->generalConfig->getSetPasswordRequestPath();

        if ($isInstalled && ! $this->updates->isCraftUpdatePending()) {
            $currentSite = $this->sites->getCurrentSite();
            $siteName = t($currentSite->getName(), category: 'site');
            $siteUrl = $currentSite->getBaseUrl();
            $systemName = Cms::systemName();
        } else {
            $currentSite = $siteName = $siteUrl = $systemName = null;
        }

        $globals = [
            'craft' => $this->craftVariable,
            'currentSite' => $currentSite,
            'currentUser' => currentUserElement(),
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'systemName' => $systemName,
            'language' => app()->getLocale(),
            'devMode' => $this->app->hasDebugModeEnabled(),
            'isInstalled' => $isInstalled,
            'loginUrl' => is_string($loginPath = $this->generalConfig->getLoginPath())
                ? Url::siteUrl($loginPath)
                : null,
            'logoutUrl' => is_string($logoutPath = $this->generalConfig->getLogoutPath())
                ? Url::siteUrl($logoutPath)
                : null,
            'setPasswordUrl' => $setPasswordRequestPath !== null ? Url::siteUrl($setPasswordRequestPath) : null,
            'now' => now(),
            'today' => today(),
            'tomorrow' => today()->addDay(),
            'yesterday' => today()->subDay(),
        ];

        event($event = new TemplateGlobalsResolving($globals));

        return $this->globals = $event->globals;
    }
}
