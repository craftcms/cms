<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Hooks;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\ElementSources;

readonly class PrepareElementSourcesVariables
{
    public function __construct(
        private ElementSources $elementSources,
    ) {}

    public function __invoke(array &$context): void
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $context['elementType'];

        $context['keyPrefix'] ??= '';
        $context['isTopLevel'] = $context['keyPrefix'] === '';

        if ($context['isTopLevel']) {
            $context['baseSortOptions'] ??= collect($elementType::sortOptions())
                ->map(fn ($option, $key) => [
                    'label' => $option['label'] ?? $option,
                    'attr' => $option['attribute'] ?? $option['orderBy'] ?? $key,
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ])
                ->values()
                ->all();

            $context['tableColumns'] ??= $this->elementSources->getAvailableTableAttributes($elementType)->all();
        }

        $context['viewModes'] ??= $elementType::indexViewModes();
    }
}
