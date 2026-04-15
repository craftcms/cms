<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;

readonly class ElementSourcesController
{
    use RespondsWithFlash;

    public function show(ElementRequest $request, ElementSources $elementSources, Fields $fields, UserGroups $userGroups): JsonResponse
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();

        // Global sort options
        $baseSortOptions = collect($elementType::sortOptions())
            ->map(fn ($option, $key) => [
                'label' => $option['label'] ?? $option,
                'attr' => $option['attribute'] ?? $option['orderBy'] ?? $key,
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();

        // Get the source info
        $sources = $elementSources->getSources($elementType, ElementSources::CONTEXT_INDEX, true)->all();
        $multiPage = $elementType::multiPageSources();

        foreach ($sources as &$source) {
            if ($multiPage) {
                // ensure we're using the EN translation here
                $language = app()->getLocale();
                app()->setLocale('en');
                $source['page'] ??= $elementType::pluralDisplayName();
                app()->setLocale($language);
            }

            if ($source['type'] === ElementSources::TYPE_HEADING) {
                continue;
            }

            // Sort options
            $source['sortOptions'] = array_merge(
                array_filter([
                    ($source['structureId'] ?? false)
                        ? [
                            'label' => t('Structure'),
                            'attr' => 'structure',
                            'defaultDir' => 'asc',
                        ]
                        : null,
                ]),
                $baseSortOptions,
                $elementSources->getSourceSortOptions($elementType, $source['key'])
                    ->map(fn ($option) => [
                        'label' => $option['label'],
                        'attr' => $option['attribute'] ?? $option['orderBy'],
                        'defaultDir' => $option['defaultDir'] ?? 'asc',
                    ])
                    ->values()
                    ->all()
            );

            $defaultSortOption = null;
            $defaultSortDir = null;

            if (isset($source['defaultSort'])) {
                if (is_string($source['defaultSort'])) {
                    $defaultSortOption = collect($source['sortOptions'])->firstWhere('attr', $source['defaultSort']);
                } elseif (is_array($source['defaultSort']) && isset($source['defaultSort'][0])) {
                    $defaultSortOption = collect($source['sortOptions'])->firstWhere('attr', $source['defaultSort'][0]);
                    if ($defaultSortOption && isset($source['defaultSort'][1])) {
                        $defaultSortDir = $source['defaultSort'][1];
                    }
                }
            }

            if (! $defaultSortOption) {
                $defaultSortOption = reset($source['sortOptions']);
            }

            $source['defaultSort'] = [
                $defaultSortOption['attr'],
                $defaultSortDir ?? $defaultSortOption['defaultDir'],
            ];

            // Available custom field attributes
            $source['availableTableAttributes'] = [];
            foreach ($elementSources->getSourceTableAttributes($elementType, $source['key']) as $key => $labelInfo) {
                $source['availableTableAttributes'][] = [$key, $labelInfo['label']];
            }

            // Selected table attributes
            $tableAttributes = $elementSources->getTableAttributes($elementType, $source['key'])->all();
            array_shift($tableAttributes);
            $source['tableAttributes'] = array_map(fn ($a) => [$a[0], $a[1]['label']], $tableAttributes);

            if ($source['type'] === ElementSources::TYPE_CUSTOM) {
                if (isset($source['condition'])) {
                    /** @var ElementConditionInterface $condition */
                    $condition = Conditions::createCondition(Arr::pull($source, 'condition'));
                    $condition->mainTag = 'div';
                    $condition->name = "sources[{$source['key']}][condition]";
                    $condition->forProjectConfig = true;
                    $condition->queryParams = ['site', 'status'];
                    $condition->addRuleLabel = t('Add a filter');

                    HtmlStack::startJsBuffer();
                    $conditionBuilderHtml = $condition->getBuilderHtml();
                    $conditionBuilderJs = HtmlStack::clearJsBuffer();
                    $source += compact('conditionBuilderHtml', 'conditionBuilderJs');
                }

                if (isset($source['sites'])) {
                    $source['sites'] = array_values(array_filter(array_map(
                        fn (int $siteId) => Sites::getSiteById($siteId)?->uid,
                        $source['sites'] ?: [],
                    )));
                }
                if (isset($source['sites']) && $source['sites'] === false) {
                    $source['sites'] = [];
                }

                if (isset($source['userGroups']) && $source['userGroups'] === false) {
                    $source['userGroups'] = [];
                }
            }
        }
        unset($source);

        $viewModes = array_map(fn (array $viewMode) => array_merge($viewMode, [
            'iconSvg' => Icons::svg($viewMode['icon'] ?? 'table'),
        ]), $elementType::indexViewModes());

        // Get the default sort options for custom sources
        $defaultSortOptions = $elementSources->getSourceSortOptions($elementType, 'custom:x')
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'attr' => $option['attribute'] ?? $option['orderBy'],
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();

        // Get the available table attributes
        $availableTableAttributes = [];

        foreach ($elementSources->getAvailableTableAttributes($elementType) as $key => $labelInfo) {
            $availableTableAttributes[] = [$key, $labelInfo['label']];
        }

        // Get previewable custom fields that should be available for all custom sources
        $customFieldAttributes = [];

        foreach ($fields->getLayoutsByType($elementType) as $fieldLayout) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                if ($field instanceof PreviewableFieldInterface) {
                    $customFieldAttributes[] = ["field:$field->uid", t($field->name, category: 'site')];
                }
            }
        }

        $condition = $elementType::createCondition();
        $condition->id = '__ID__';
        $condition->name = 'sources[__SOURCE_KEY__][condition]';
        $condition->mainTag = 'div';
        $condition->forProjectConfig = true;
        $condition->queryParams = ['site', 'status'];
        $condition->addRuleLabel = t('Add a filter');

        HtmlStack::startJsBuffer();
        $conditionBuilderHtml = $condition->getBuilderHtml();
        $conditionBuilderJs = HtmlStack::clearJsBuffer();

        $userGroups = $userGroups->getAllGroups()
            ->map(fn (UserGroup $group) => [
                'label' => t($group->name, category: 'site'),
                'value' => $group->uid,
            ])
            ->all();

        return new JsonResponse([
            'multiPage' => $multiPage,
            'sources' => $sources,
            'pageSettings' => $elementSources->getPageSettings($elementType),
            'viewModes' => $viewModes,
            'baseSortOptions' => $baseSortOptions,
            'defaultSortOptions' => $defaultSortOptions,
            'availableTableAttributes' => $availableTableAttributes,
            'customFieldAttributes' => $customFieldAttributes,
            'elementTypeName' => $elementType::displayName(),
            'conditionBuilderHtml' => $conditionBuilderHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
            'userGroups' => $userGroups,
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function store(ElementRequest $request, ElementSources $elementSources, ProjectConfig $projectConfig)
    {
        $elementType = $request->elementType();
        $multiPage = $elementType::multiPageSources();

        // Get the old source configs
        $oldSourceConfigs = $projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES.".$elementType") ?? [];
        $oldSourceConfigs = collect($oldSourceConfigs)
            ->keyBy('key')
            ->all();

        $sourceOrder = $request->array('sourceOrder');
        $sourceSettings = $request->array('sources');
        $newSourceConfigs = [];
        $disabledSourceKeys = [];

        if ($multiPage) {
            $sourcePages = $request->array('sourcePages');
            $pageSettings = $request->array('pageSettings');
            $sourcePageIndexes = [];
        }

        // Normalize to the way it's stored in the DB
        foreach ($sourceOrder as $key) {
            $type = match (true) {
                str_starts_with((string) $key, 'custom:') => ElementSources::TYPE_CUSTOM,
                str_starts_with((string) $key, 'heading:') => ElementSources::TYPE_HEADING,
                default => ElementSources::TYPE_NATIVE,
            };

            $isCustom = $type === ElementSources::TYPE_CUSTOM;
            $sourceConfig = [
                'type' => $type,
                'key' => $key,
            ];

            if (isset($sourcePages[$key])) {
                $sourceConfig['page'] = $sourcePages[$key];
            }

            // Were new settings posted?
            if (isset($sourceSettings[$key])) {
                $postedSettings = $sourceSettings[$key];

                if ($type !== ElementSources::TYPE_HEADING) {
                    $sourceConfig['tableAttributes'] = array_values(array_filter($postedSettings['tableAttributes'] ?? [])) ?: '-';
                }

                if (isset($postedSettings['defaultSort'])) {
                    $sourceConfig['defaultSort'] = $postedSettings['defaultSort'];
                }

                if (isset($postedSettings['defaultViewMode'])) {
                    $sourceConfig['defaultViewMode'] = $postedSettings['defaultViewMode'];
                }

                if ($isCustom) {
                    $sourceConfig += [
                        'label' => $postedSettings['label'],
                        'condition' => Conditions::createCondition($postedSettings['condition'])->getConfig(),
                    ];

                    if (isset($postedSettings['sites']) && $postedSettings['sites'] !== '*') {
                        $sourceConfig['sites'] = is_array($postedSettings['sites']) ? $postedSettings['sites'] : false;
                    }

                    if (isset($postedSettings['userGroups']) && $postedSettings['userGroups'] !== '*') {
                        $sourceConfig['userGroups'] = is_array($postedSettings['userGroups']) ? $postedSettings['userGroups'] : false;
                    }
                } elseif ($type === ElementSources::TYPE_HEADING) {
                    $sourceConfig['heading'] = $postedSettings['heading'];
                } elseif (isset($postedSettings['enabled'])) {
                    $sourceConfig['disabled'] = ! $postedSettings['enabled'];
                    if ($sourceConfig['disabled']) {
                        $disabledSourceKeys[] = $key;
                    }
                }
            } elseif (isset($oldSourceConfigs[$key])) {
                $sourceConfig += $oldSourceConfigs[$key];
                if (! empty($sourceConfig['disabled'])) {
                    $disabledSourceKeys[] = $key;
                }
            } elseif ($isCustom) {
                // Ignore it
                continue;
            }

            $newSourceConfigs[] = $sourceConfig;

            if ($multiPage) {
                $sourcePageIndexes[] = array_search($sourceConfig['page'], array_keys($pageSettings));
            }
        }

        if ($multiPage) {
            /** @phpstan-ignore-next-line */
            array_multisort($sourcePageIndexes, SORT_NUMERIC, range(1, count($newSourceConfigs)), SORT_NUMERIC, $newSourceConfigs);
        }

        $elementSources->saveSources($elementType, $newSourceConfigs);

        if ($multiPage) {
            $elementSources->savePageSettings($elementType, array_map(
                fn (array $settings) => array_filter($settings, fn ($setting) => $setting !== null && $setting !== ''),
                $pageSettings,
            ));
        }

        return $this->asSuccess(t('Source settings saved'), data: [
            'disabledSourceKeys' => $disabledSourceKeys,
        ]);
    }
}
