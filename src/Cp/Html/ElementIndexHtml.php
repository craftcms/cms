<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementIndexState;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

#[Singleton]
readonly class ElementIndexHtml
{
    public function __construct(
        private Sites $sites,
        private ElementSources $elementSources,
        private ElementIndexState $indexState,
    ) {}

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $config
     */
    public function html(string $elementType, array $config = []): string
    {
        $config += [
            'class' => null,
            'context' => 'index',
            'defaultSort' => null,
            'defaultTableColumns' => null,
            'defaultViewMode' => 'table',
            'fieldLayouts' => [],
            'id' => sprintf('element-index-%s', mt_rand()),
            'jsSettings' => [],
            'registerJs' => true,
            'showSiteMenu' => 'auto',
            'siteIds' => null,
            'showStatusMenu' => 'auto',
            'statuses' => null,
            'sources' => null,
        ];

        // Note that 'auto' is deliberately left alone here rather than resolved
        // via ElementIndexState::showStatusMenu(): the toolbar template treats
        // any truthy value as "render the menu" and lets the JS index fill in
        // the statuses, and resolving it server-side would change what renders.
        if ($config['showStatusMenu'] !== 'auto') {
            $config['showStatusMenu'] = (bool) $config['showStatusMenu'];
        }

        $config['showSiteMenu'] = $this->indexState->showSiteMenu($elementType, $config['showSiteMenu']);

        $siteIds = $config['siteIds'] ?? $this->sites->getEditableSiteIds()->all();

        $sortOptions = $this->indexState->sortOptions($elementType)
            ->map(fn (array $option) => [
                'label' => $option['label'] ?? $option['option'],
                'attr' => $option['attribute'] ?? $option['key'],
                'defaultDir' => $option['defaultDir'],
            ])
            ->all();
        $sortOptionsKey = 'baseSortOptions';

        // No source is resolved server-side here — the JS index picks one — so
        // only the element type's common attributes are offered.
        $tableColumns = $this->indexState->tableColumns($elementType)->all();

        if ($config['sources'] !== false) {
            $sources = $this->indexState->sources(
                $elementType,
                $config['context'],
                restrictTo: is_array($config['sources']) ? $config['sources'] : null,
            );
            $showSidebar = $this->indexState->showSidebar($sources);
        } else {
            $showSidebar = false;
            $sources = Collection::make([
                [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => '__IMP__',
                    'label' => t('All elements'),
                    'hasThumbs' => $elementType::hasThumbs(),
                    'defaultSort' => $config['defaultSort'],
                    'defaultViewMode' => $config['defaultViewMode'],
                    'fieldLayouts' => $config['fieldLayouts'],
                ],
            ]);

            // if field layouts were supplied, merge in additional table columns and sort columns
            if (! empty($config['fieldLayouts'])) {
                $sortOptions = array_merge(
                    $sortOptions,
                    array_map(fn (array $option) => [
                        'label' => $option['label'],
                        'attr' => $option['attribute'],
                        'defaultDir' => $option['defaultDir'],
                    ], $this->elementSources->getSortOptionsForFieldLayouts($config['fieldLayouts'])->all()),
                );
                // Don't let sources.twig merge sortOptions with anything else!
                $sortOptionsKey = 'sortOptions';

                $tableColumns = array_merge(
                    $tableColumns,
                    $this->elementSources->getTableAttributesForFieldLayouts($config['fieldLayouts'])->all(),
                );
            }
        }

        // If all the sources are site-specific, filter out any unneeded site IDs
        if (
            $config['showSiteMenu'] &&
            $sources->every(fn (array $source) => $source['type'] === 'heading' || isset($source['sites']))
        ) {
            $representedSiteIds = [];
            foreach ($sources as $source) {
                if (isset($source['sites'])) {
                    foreach ($source['sites'] as $siteId) {
                        $representedSiteIds[$siteId] = true;
                    }
                }
            }
            $siteIds = array_values(array_filter($siteIds, fn (int $siteId) => isset($representedSiteIds[$siteId])));
        }

        if ($config['registerJs']) {
            HtmlStack::jsWithVars(fn ($elementType, $id, $settings) => <<<JS
Craft.createElementIndex($elementType, $('#' + $id), $settings)
JS, [
                $elementType,
                InputNamespace::namespaceId($config['id']),
                array_merge(
                    [
                        'context' => $config['context'],
                        'namespace' => InputNamespace::get(),
                        'prevalidate' => $config['prevalidate'] ?? false,
                    ],
                    $config['jsSettings']
                ),
            ]);
        }

        $html = Html::beginTag('div', [
            'id' => $config['id'],
            'class' => array_merge(
                ['element-index'],
                ($showSidebar ? ['has-sidebar'] : []),
                (in_array($config['context'], ['embedded-index', 'pane']) ? ['pane', 'padding-s', 'hairline'] : []),
                Html::explodeClass($config['class']),
            ),
            'data' => [
                'site-ids' => $siteIds,
            ],
        ]).
            Html::beginTag('div', [
                'class' => array_filter([
                    'sidebar',
                    (! $showSidebar ? 'hidden' : null),
                ]),
            ]).
            Html::tag('nav', template('_elements/sources', [
                'elementType' => $elementType,
                'sources' => $sources,
                $sortOptionsKey => $sortOptions,
                'tableColumns' => $tableColumns,
                'defaultTableColumns' => $config['defaultTableColumns'],
            ], templateMode: TemplateMode::Cp)).
            Html::endTag('div').
            Html::beginTag('div', ['class' => 'main']).
            Html::beginTag('div', ['class' => ['toolbar', 'flex']]).
            template('_elements/toolbar', [
                'elementType' => $elementType,
                'context' => $config['context'],
                'showStatusMenu' => $config['showStatusMenu'],
                'elementStatuses' => $config['statuses'],
                'showSiteMenu' => $config['showSiteMenu'],
                'siteIds' => $siteIds,
                'canHaveDrafts' => $elementType::hasDrafts(),
            ], templateMode: TemplateMode::Cp).
            Html::endTag('div'). // .toolbar
            Html::tag('div', attributes: ['class' => 'elements']).
            Html::endTag('div'); // .main

        if ($this->contextIsAdministrative($config['context'])) {
            $html .= Html::beginTag('div', [
                'class' => ['footer', 'flex', 'flex-justify'],
            ]).
                template('_elements/footer', templateMode: TemplateMode::Cp).
                Html::endTag('div'); // .footer
        }

        return $html.
            Html::endTag('div'); // .element-index;
    }

    private function contextIsAdministrative(string $context): bool
    {
        return in_array($context, ['index', 'embedded-index', 'field']);
    }
}
