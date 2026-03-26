<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Hooks;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Site\Sites;

use function CraftCms\Cms\t;

readonly class PrepareElementIndexVariables
{
    public function __construct(
        private ElementSources $elementSources,
        private Sites $sites,
    ) {}

    public function __invoke(array &$context): void
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $context['elementType'];

        $context['title'] ??= $elementType::pluralDisplayName();
        $context['context'] = 'index';

        $context['sources'] = $this->elementSources->getSources(
            $elementType,
            withDisabled: true,
            page: $context['page'] ?? null,
        )->all();

        $context['showSiteMenu'] = $this->sites->isMultiSite() ? ($context['showSiteMenu'] ?? 'auto') : false;
        if ($context['showSiteMenu'] === 'auto') {
            $context['showSiteMenu'] = $elementType::isLocalized();
        }

        $context['elementDisplayName'] = $elementType::displayName();
        $context['elementPluralDisplayName'] = $elementType::pluralDisplayName();
        $context['canHaveDrafts'] ??= $elementType::hasDrafts();

        if (isset($context['page'])) {
            if (isset($context['sources'][0]['page'])) {
                $context['title'] = t($context['sources'][0]['page'], category: 'site');
            }

            $context['selectedSubnavItem'] = $this->elementSources->pageNameId($context['page']);
        }
    }
}
