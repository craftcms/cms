<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Hooks;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Site\Sites;

readonly class PrepareElementToolbarVariables
{
    public function __construct(
        private Sites $sites,
    ) {}

    /** @param array<string, mixed> $context */
    public function __invoke(array &$context): void
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $context['elementType'];

        $context['context'] ??= 'index';
        $context['isAdministrative'] = match ($context['context']) {
            'index', 'embedded-index' => true,
            default => false,
        };
        $context['showStatusMenu'] ??= 'auto';
        if ($context['showStatusMenu'] === 'auto') {
            $context['showStatusMenu'] = $elementType::hasStatuses();
        }
        $context['showSiteMenu'] = $this->sites->isMultiSite() ? ($context['showSiteMenu'] ?? 'auto') : false;
        if ($context['showSiteMenu'] === 'auto') {
            $context['showSiteMenu'] = $elementType::isLocalized();
        }
        $context['idPrefix'] = sprintf('elementtoolbar%s-', mt_rand());

        if ($context['showStatusMenu']) {
            $context['elementStatuses'] ??= $elementType::statuses();
            if (count($context['elementStatuses']) < 2) {
                $context['showStatusMenu'] = false;
            }
        }
    }
}
