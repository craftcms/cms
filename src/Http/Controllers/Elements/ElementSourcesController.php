<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Enums\ChipThumbSize;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\ElementSourceUi;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesSourceSettings;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Ui\Controls\Choice;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
readonly class ElementSourcesController
{
    use RespondsWithFlash;
    use SavesSourceSettings;

    public function show(ElementIndexRequest $request, ElementSources $elementSources, ElementSourceUi $sourceUi): JsonResponse
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $multiPage = $elementType::multiPageSources();
        $sources = $elementSources->getSources($elementType, ElementSources::CONTEXT_INDEX, true);
        $canEditShared = self::canEditShared();

        return new JsonResponse([
            'multiPage' => $multiPage,
            // Anyone can look; only those who may change project config can
            // change anything but their own sources.
            'canEditShared' => $canEditShared,
            'sources' => $sources
                ->map(fn (array $source) => [
                    'key' => $source['key'] ?? null,
                    'type' => $source['type'],
                    'label' => $source['label'] ?? null,
                    'heading' => $source['heading'] ?? null,
                    // Shown under the label, as the legacy modal did.
                    'handle' => $source['data']['handle'] ?? null,
                    'page' => $multiPage ? ($source['page'] ?? $this->defaultPage($elementType)) : null,
                    'personal' => (bool) ($source['personal'] ?? false),
                    'readOnly' => self::isReadOnly($source, $canEditShared),
                    // ElementSources synthesizes a keyless blank heading as a
                    // separator. Nothing can address it by Control path and
                    // store() can't save it, so it gets no UI.
                    'ui' => ($source['key'] ?? '') !== ''
                        // One kept to yourself can hold rules project config can't.
                        ? $sourceUi->payload(
                            $elementType,
                            $source,
                            withUserGroups: $canEditShared,
                            forProjectConfig: ! ($source['personal'] ?? false),
                            readOnly: self::isReadOnly($source, $canEditShared),
                        )
                        : null,
                ])
                ->values()
                ->all(),
            'pageSettings' => $elementSources->getPageSettings($elementType),
            'elementTypeName' => $elementType::displayName(),
        ]);
    }

    /**
     * Returns the settings UI for a single source — for one the client just
     * added, and for {@see UiPayload} refreshes.
     */
    public function ui(ElementIndexRequest $request, ElementSources $elementSources, ElementSourceUi $sourceUi): JsonResponse
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
            'withUserGroups' => ['nullable', 'boolean'],
            'withLabel' => ['nullable', 'boolean'],
            'forProjectConfig' => ['nullable', 'boolean'],
            'part' => ['nullable', Rule::in(['filters', 'view'])],
            'edited' => ['nullable', 'array'],
            'edited.*' => [Rule::in(['filters', 'sort', 'columns', 'viewMode', 'thumbSize'])],
        ]);
        $canEditShared = self::canEditShared();
        $settings = $data['settings'] ?? [];

        // Anyone else can only keep a source to themselves.
        if (! $canEditShared) {
            $settings['visibility'] = 'me';
        }

        $withUserGroups = $canEditShared && ($data['withUserGroups'] ?? true);
        // A source kept to yourself never goes into project config.
        $forProjectConfig = ($data['forProjectConfig'] ?? true)
            && ($settings['visibility'] ?? null) !== 'me';

        $source = $elementSources->getSources($elementType, ElementSources::CONTEXT_INDEX, true)
            ->firstWhere('key', $data['sourceKey']);

        $dropped = [];

        // A new source can start from an index's filters, which may hold rules
        // project config can't.
        if ($source === null && $forProjectConfig && is_array($settings['condition'] ?? null)) {
            ['condition' => $settings['condition'], 'dropped' => $dropped] =
                $sourceUi->storableCondition($elementType, $settings['condition']);
        }

        $payload = $sourceUi->payload(
            $elementType,
            $source ?? $sourceUi->blankSource($data['type'], $data['sourceKey']),
            $settings,
            isNew: $source === null,
            withUserGroups: $withUserGroups,
            withLabel: $data['withLabel'] ?? true,
            forProjectConfig: $forProjectConfig,
            part: $data['part'] ?? null,
            edited: $data['edited'] ?? [],
            droppedRules: $dropped,
            readOnly: $source !== null && ! $canEditShared && ! ($source['personal'] ?? false),
        );

        $scope = $data['scope'] ?? [];

        // No head/body HTML: this endpoint resolves a UI payload and renders
        // nothing, so draining HtmlStack would ship the whole CP asset bootstrap
        // — initializers for elements that only exist on a full page render.
        // A server-rendered Control fetches its own assets when it renders.
        return new JsonResponse([
            'ui' => $scope === [] ? $payload : $payload->forScope($scope),
            'droppedRules' => $dropped,
            'userGroupOptions' => $withUserGroups ? null : $sourceUi->userGroupOptions(),
        ]);
    }

    /**
     * Whether a source can only be looked at: any but the user's own, for
     * anyone who can't change shared sources.
     *
     * @param  array<string, mixed>  $source
     */
    private static function isReadOnly(array $source, bool $canEditShared): bool
    {
        return ! $canEditShared && ! ($source['personal'] ?? false);
    }

    /** Whether the current user may change sources in project config. */
    private static function canEditShared(): bool
    {
        return (bool) currentUser()?->isAdmin() && Cms::config()->allowAdminChanges;
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

    public function store(ElementIndexRequest $request, ElementSources $elementSources, ProjectConfig $projectConfig, Navigation $navigation): Response
    {
        $elementType = $request->elementType();

        // Sources kept to yourself aren't project config, so they can't come
        // through here.
        $request->validate([
            'sources.*.visibility' => ['nullable', Rule::in(['everyone', 'groups'])],
        ]);
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

                if (isset($postedSettings['defaultThumbSizes'])) {
                    $sourceConfig['defaultThumbSizes'] = ChipThumbSize::indexDefaults([
                        ...($oldSourceConfigs[$key]['defaultThumbSizes'] ?? []),
                        ...(array) $postedSettings['defaultThumbSizes'],
                    ]);
                } elseif (isset($oldSourceConfigs[$key]['defaultThumbSizes'])) {
                    $sourceConfig['defaultThumbSizes'] = $oldSourceConfigs[$key]['defaultThumbSizes'];
                }

                if (isset($postedSettings['showBadgeCount'])) {
                    $sourceConfig['showBadgeCount'] = filter_var($postedSettings['showBadgeCount'], FILTER_VALIDATE_BOOLEAN);
                }

                if ($isCustom) {
                    $sourceConfig += [
                        'label' => $postedSettings['label'],
                        'condition' => Conditions::createCondition($postedSettings['condition'])->getConfig(),
                    ];

                    // Not something the settings edit, so it stays as it was.
                    if (isset($oldSourceConfigs[$key]['baseSource'])) {
                        $sourceConfig['baseSource'] = $oldSourceConfigs[$key]['baseSource'];
                    }

                    if (isset($postedSettings['sites']) && ! self::isAllScope($postedSettings['sites'])) {
                        $sourceConfig['sites'] = $this->sourceScope($postedSettings['sites']);
                    }

                    // Without a visibility, as the legacy modal posts, the
                    // groups decide.
                    $forGroups = ($postedSettings['visibility'] ?? 'groups') === 'groups';

                    if ($forGroups && isset($postedSettings['userGroups']) && ! self::isAllScope($postedSettings['userGroups'])) {
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

        // Pages whose sources are all disabled count too, so enabling one
        // doesn't move a page the nav was already customized with.
        $oldEntryPages = $elementType === Entry::class
            ? $elementSources->getSources(Entry::class, ElementSources::CONTEXT_NAVIGATION, withDisabled: true)
                ->pluck('page')->filter()->unique()->values()->all()
            : null;

        $elementSources->saveSources($elementType, $newSourceConfigs);

        if ($multiPage) {
            $elementSources->savePageSettings($elementType, array_map(
                fn (array $settings) => array_filter($settings, fn ($setting) => $setting !== null && $setting !== ''),
                $pageSettings,
            ));
        }

        if ($oldEntryPages !== null) {
            $navigation->placeAddedEntryPages($oldEntryPages);
        }

        // Where the index should land next: the source being edited, by the link
        // the nav gives it — on whichever page it now lives.
        $landingSource = $request->input('landingSource');
        $redirect = is_string($landingSource) && $landingSource !== ''
            ? $navigation->sourceUrl($elementType, $landingSource)
            : null;

        return $this->asSuccess(t('Source settings saved'), data: [
            'disabledSourceKeys' => $disabledSourceKeys,
        ], redirect: $redirect);
    }

    /**
     * Saves an index's changes onto the source it's showing: its view, and on
     * a custom source, its filters, which join the source's own condition.
     */
    public function update(ElementIndexRequest $request, ElementSources $elementSources, ElementSourceUi $sourceUi, Navigation $navigation): Response
    {
        $elementType = $request->elementType();
        $request->validate([
            'source' => ['required', 'string'],
            ...$this->sourceSettingRules(),
        ]);

        $key = $request->string('source')->value();
        $source = $elementSources->findSource($elementType, $key);

        if ($source === null || isset($source['keyPath']) || ($source['personal'] ?? false)) {
            abort(404, t('That source can’t be saved.'));
        }

        $settings = $this->postedViewSettings($request, $source);

        // A custom source's filters are its whole condition, as the index showed it.
        $condition = $request->array('condition');

        if ($source['type'] !== ElementSources::TYPE_CUSTOM) {
            if (! empty($condition['conditionRules'])) {
                throw ValidationException::withMessages([
                    'condition' => t('Built-in sources can’t store filters.'),
                ]);
            }
        } elseif ($condition !== []) {
            ['condition' => $storable, 'dropped' => $dropped] = $sourceUi->storableCondition($elementType, $condition);

            if ($dropped !== []) {
                throw ValidationException::withMessages([
                    'condition' => t('These filters can’t be saved to a source: {rules}. Remove them, or save as a new source.', [
                        'rules' => implode(', ', array_unique($dropped)),
                    ]),
                ]);
            }

            $settings['condition'] = $this->sourceCondition($storable);
        }

        $elementSources->updateSource($elementType, $key, $settings);

        return $this->asSuccess(
            t('Saved changes to “{source}”.', ['source' => $source['label'] ?? $key]),
            redirect: $navigation->sourceUrl($elementType, $key),
        );
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
}
