<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSourceForm;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Conditions;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class ElementSourcesController
{
    use RespondsWithFlash;

    public function show(ElementIndexRequest $request, ElementSources $elementSources, ElementSourceForm $sourceForm): JsonResponse
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $multiPage = $elementType::multiPageSources();
        $sources = $elementSources->getSources($elementType, ElementSources::CONTEXT_INDEX, true);

        return new JsonResponse([
            'multiPage' => $multiPage,
            'sources' => $sources
                ->map(fn (array $source) => [
                    'key' => $source['key'] ?? null,
                    'type' => $source['type'],
                    'label' => $source['label'] ?? null,
                    'heading' => $source['heading'] ?? null,
                    'page' => $multiPage ? ($source['page'] ?? $this->defaultPage($elementType)) : null,
                    // ElementSources synthesizes a keyless blank heading as a
                    // separator. Nothing can address it by Control path and
                    // store() can't save it, so it gets no Form.
                    'form' => ($source['key'] ?? '') !== ''
                        ? $sourceForm->payload($elementType, $source)
                        : null,
                ])
                ->values()
                ->all(),
            'pageSettings' => $elementSources->getPageSettings($elementType),
            'elementTypeName' => $elementType::displayName(),
        ]);
    }

    /**
     * Returns the settings Form for a single source — for one the client just
     * added, and for {@see FormPayload} refreshes.
     */
    public function form(ElementIndexRequest $request, ElementSources $elementSources, ElementSourceForm $sourceForm): JsonResponse
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();

        $data = $request->validate([
            'sourceKey' => ['required', 'string'],
            'type' => ['required', Rule::in([
                ElementSources::TYPE_NATIVE,
                ElementSources::TYPE_CUSTOM,
                ElementSources::TYPE_HEADING,
            ])],
            'settings' => ['nullable', 'array'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['string'],
        ]);

        $source = $elementSources->getSources($elementType, ElementSources::CONTEXT_INDEX, true)
            ->firstWhere('key', $data['sourceKey']);

        $payload = $sourceForm->payload(
            $elementType,
            $source ?? $sourceForm->blankSource($data['type'], $data['sourceKey']),
            $data['settings'] ?? [],
            isNew: $source === null,
        );

        $scope = $data['scope'] ?? [];

        // No head/body HTML: this endpoint resolves a Form payload and renders
        // nothing, so draining HtmlStack would ship the whole CP asset bootstrap
        // — initializers for elements that only exist on a full page render.
        // A server-rendered Control fetches its own assets when it renders.
        return new JsonResponse([
            'form' => $scope === [] ? $payload : $payload->forScope($scope),
        ]);
    }

    /**
     * The page a multi-page source falls back to. It's a project config key, so
     * it must not be localized.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    private function defaultPage(string $elementType): string
    {
        $language = app()->getLocale();
        app()->setLocale('en');
        $page = $elementType::pluralDisplayName();
        app()->setLocale($language);

        return $page;
    }

    public function store(ElementIndexRequest $request, ElementSources $elementSources, ProjectConfig $projectConfig): Response
    {
        $elementType = $request->elementType();
        $multiPage = $elementType::multiPageSources();

        // Get the old source configs
        $oldSourceConfigs = $projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES.".$elementType") ?? [];
        $oldSourceConfigs = collect(is_array($oldSourceConfigs) ? $oldSourceConfigs : [])
            ->keyBy('key')
            ->all();

        $sourceOrder = $request->array('sourceOrder');
        $sourceSettings = $request->array('sources');
        $newSourceConfigs = [];
        $disabledSourceKeys = [];
        $sourcePageIndexes = [];

        if ($multiPage) {
            $sourcePages = $request->array('sourcePages');
            $pageSettings = $request->array('pageSettings');
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
                    $sourceConfig['tableAttributes'] = $this->tableAttributeKeys($postedSettings['tableAttributes'] ?? []);
                }

                if (isset($postedSettings['defaultSort'])) {
                    $sourceConfig['defaultSort'] = $this->defaultSort($postedSettings['defaultSort']);
                }

                if (isset($postedSettings['defaultViewMode'])) {
                    $sourceConfig['defaultViewMode'] = $postedSettings['defaultViewMode'];
                }

                if ($isCustom) {
                    $sourceConfig += [
                        'label' => $postedSettings['label'],
                        'condition' => Conditions::createCondition($postedSettings['condition'])->getConfig(),
                    ];

                    if (isset($postedSettings['sites']) && ! self::isAllScope($postedSettings['sites'])) {
                        $sourceConfig['sites'] = $this->sourceScope($postedSettings['sites']);
                    }

                    if (isset($postedSettings['userGroups']) && ! self::isAllScope($postedSettings['userGroups'])) {
                        $sourceConfig['userGroups'] = $this->sourceScope($postedSettings['userGroups']);
                    }
                } elseif ($type === ElementSources::TYPE_HEADING) {
                    $sourceConfig['heading'] = $postedSettings['heading'] ?? '';
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
                $sourcePageIndexes[] = array_search($sourceConfig['page'] ?? null, array_keys($pageSettings));
            }
        }

        if ($multiPage) {
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

    /**
     * Accepts the Form's `['attr' => …, 'dir' => …]` and the legacy
     * `[attr, dir]` list alike.
     */
    private function defaultSort(mixed $defaultSort): mixed
    {
        if (is_array($defaultSort) && (isset($defaultSort['attr']) || isset($defaultSort['dir']))) {
            return array_values(array_filter(
                [$defaultSort['attr'] ?? null, $defaultSort['dir'] ?? null],
                fn (mixed $value) => $value !== null && $value !== '',
            ));
        }

        return $defaultSort;
    }

    /**
     * Whether a posted `sites`/`userGroups` scope means “all”, which project
     * config records by omitting the key.
     *
     * The “All” checkbox posts {@see Choice::ALL_VALUE} as the sole member of
     * the control's array. A scope that has never been narrowed is seeded as
     * the bare token instead, and posts back unchanged.
     */
    private static function isAllScope(mixed $scope): bool
    {
        return $scope === Choice::ALL_VALUE || $scope === [Choice::ALL_VALUE];
    }

    /**
     * Normalizes a custom source's `sites`/`userGroups` scope. An empty
     * selection means “none”, which project config stores as `false` — the
     * legacy modal posted nothing at all here, so the setting silently
     * reverted to “all” on every save.
     *
     * @return list<mixed>|false
     */
    private function sourceScope(mixed $scope): array|false
    {
        if (! is_array($scope) || $scope === []) {
            return false;
        }

        return array_values($scope);
    }

    /**
     * @param  array<array-key, mixed>  $attributes
     * @return list<mixed>|string
     */
    private function tableAttributeKeys(array $attributes): array|string
    {
        $attributes = collect($attributes)
            ->map(fn (mixed $attribute) => data_get($attribute, 'value', $attribute))
            ->filter()
            ->values()
            ->all();

        return $attributes ?: '-';
    }
}
