<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;

readonly class SystemMessageRenderContext
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private Sites $sites,
        private Twig $twig,
    ) {}

    public function run(?int $siteId, string $language, callable $callback): mixed
    {
        $currentSite = $messageSite = $twig = null;
        $originalLanguage = app()->getLocale();
        $generateTransformsBeforePageLoad = $this->generalConfig->generateTransformsBeforePageLoad;

        $originalTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::Site);

        try {
            if ($siteId !== null) {
                $currentSite = $this->sites->getCurrentSite();

                if ($siteId !== $currentSite->id) {
                    $messageSite = $this->sites->getSiteById($siteId);

                    if ($messageSite) {
                        $this->sites->setCurrentSite($messageSite);
                        // Reset Twig so any global sets and singles get reloaded for the new site.
                        $twig = $this->twig->get();
                        $this->twig->set($this->twig->create());
                    }
                }
            }

            app()->setLocale($language);

            // Temporarily disable lazy transform generation.
            $this->generalConfig->generateTransformsBeforePageLoad = true;

            return $callback();
        } finally {
            app()->setLocale($originalLanguage);
            $this->generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;

            if ($currentSite && $messageSite) {
                $this->sites->setCurrentSite($currentSite);
            }

            TemplateMode::set($originalTemplateMode);

            if ($twig) {
                $this->twig->set($twig);
            }
        }
    }
}
