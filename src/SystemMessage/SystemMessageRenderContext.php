<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Twig\Twig;
use CraftCms\Cms\View\TemplateMode;

/**
 * @since 6.0.0
 */
readonly class SystemMessageRenderContext
{
    public function __construct(
        private AssetTransformers $assetTransformers,
        private Sites $sites,
        private Twig $twig,
    ) {}

    public function run(?int $siteId, string $language, callable $callback): mixed
    {
        return TemplateMode::with(TemplateMode::Site, function () use ($siteId, $language, $callback): mixed {
            $currentSite = $siteId !== null ? $this->sites->getCurrentSite() : null;
            $messageSite = $currentSite && $siteId !== $currentSite->id ? $this->sites->getSiteById($siteId) : null;
            $originalLanguage = app()->getLocale();
            $twig = null;

            try {
                if ($messageSite) {
                    $this->sites->setCurrentSite($messageSite);
                    // A new Twig environment loads the global sets and singles of the message's site
                    $twig = $this->twig->create();
                }

                app()->setLocale($language);
                $render = fn (): mixed => $this->assetTransformers->withImmediateTransforms($callback);

                return $twig ? $this->twig->with($twig, TemplateMode::Site, $render) : $render();
            } finally {
                app()->setLocale($originalLanguage);

                if ($currentSite && $messageSite) {
                    $this->sites->setCurrentSite($currentSite);
                }
            }
        });
    }
}
