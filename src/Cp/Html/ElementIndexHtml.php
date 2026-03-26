<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
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
    ) {}

    /**
     * @param  class-string<ElementInterface>  $elementType
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
            'showStatusMenu' => 'auto',
            'statuses' => null,
            'sources' => null,
        ];

        if ($config['showStatusMenu'] !== 'auto') {
            $config['showStatusMenu'] = (bool) $config['showStatusMenu'];
        }

        $config['showSiteMenu'] = $config['showSiteMenu'] === 'auto'
            ? $elementType::isLocalized()
            : (bool) $config['showSiteMenu'];

        $siteIds = $this->sites->getEditableSiteIds()->all();

        $sortOptions = Collection::make($elementType::sortOptions())
            ->map(fn ($option, $key) => [
                'label' => $option['label'] ?? $option,
                'attr' => $option['attribute'] ?? $option['orderBy'] ?? $key,
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();
        $sortOptionsKey = 'baseSortOptions';

        $tableColumns = $this->elementSources->getAvailableTableAttributes($elementType)->all();

        if ($config['sources'] !== false) {
            if (is_array($config['sources'])) {
                $indexedSourceKeys = array_flip($config['sources']);
                $allSources = $this->elementSources->getSources($elementType, $config['context']);
                $sources = [];

                foreach ($allSources as $source) {
                    if ($source['type'] === ElementSources::TYPE_HEADING) {
                        $sources[] = $source;
                    } elseif (isset($indexedSourceKeys[$source['key']])) {
                        $sources[] = $source;
                        // Unset so we can keep track of which keys couldn't be found
                        unset($indexedSourceKeys[$source['key']]);
                    }
                }

                $sources = ElementSources::filterExtraHeadings($sources);

                // Did we miss any source keys? (This could happen if some are nested)
                if (! empty($indexedSourceKeys)) {
                    foreach (array_keys($indexedSourceKeys) as $key) {
                        $source = ElementHelper::findSource($elementType, $key, $config['context']);
                        if ($source !== null) {
                            // If it was listed after another source key that made it in, insert it there
                            $pos = array_search($key, $config['sources']);
                            $inserted = false;
                            if ($pos > 0) {
                                $prevKey = $config['sources'][$pos - 1];
                                foreach ($sources as $i => $otherSource) {
                                    if (($otherSource['key'] ?? null) === $prevKey) {
                                        array_splice($sources, $i + 1, 0, [$source]);
                                        $inserted = true;
                                        break;
                                    }
                                }
                            }
                            if (! $inserted) {
                                $sources[] = $source;
                            }
                        }
                    }
                }
            } else {
                $sources = $this->elementSources->getSources($elementType, $config['context']);
            }

            // Show the sidebar if there are at least two (non-heading) sources
            $showSidebar = (function () use ($sources): bool {
                $foundSource = false;
                foreach ($sources as $source) {
                    if ($source['type'] !== ElementSources::TYPE_HEADING) {
                        if ($foundSource || ! empty($source['nested'])) {
                            return true;
                        }
                        $foundSource = true;
                    }
                }

                return false;
            })();
        } else {
            $showSidebar = false;
            $sources = [
                [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => '__IMP__',
                    'label' => t('All elements'),
                    'hasThumbs' => $elementType::hasThumbs(),
                    'defaultSort' => $config['defaultSort'],
                    'defaultViewMode' => $config['defaultViewMode'],
                    'fieldLayouts' => $config['fieldLayouts'],
                ],
            ];

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
            Collection::make($sources)->every(fn (array $source) => $source['type'] === 'heading' || isset($source['sites']))
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
                ($config['context'] === 'embedded-index' ? ['pane', 'padding-s', 'hairline'] : []),
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
