<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\base\ElementInterface;
use craft\config\GeneralConfig;
use craft\helpers\ArrayHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\ElementQueryFactory;
use craft\mcp\McpActor;
use craft\mcp\schema\ConditionSchema;
use craft\mcp\schema\ConfigurationSchema;
use craft\services\ElementSources as ElementSourcesService;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserGroups;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Reads and saves an element type’s index sidebar, as the control panel’s Customize Sources modal does.
 *
 * @since 5.13.0
 */
class ElementSources
{
    private const UidListSchema = [
        'type' => ['array', 'null'],
        'items' => ['type' => 'string', 'format' => 'uuid'],
    ];

    private const SourceSchema = [
        'type' => 'object',
        'properties' => [
            'type' => ['type' => 'string', 'enum' => ['heading', 'native', 'custom']],
            'key' => ['type' => 'string', 'description' => 'Required for native sources. Send existing headings and custom sources with their key; omit it to create one. Headings in Craft’s default sidebar have no key, so send them as they are: the save recreates them with keys, which later reads return.'],
            'heading' => ['type' => 'string', 'description' => 'Headings only.'],
            'label' => ['type' => 'string', 'description' => 'Custom sources only. Native source labels are read-only and ignored when sent.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Native sources only. false hides the source. Custom sources can’t be hidden; delete them or limit them with userGroups.'],
            'columns' => [
                'type' => ['array', 'null'],
                'items' => ['type' => 'string'],
                'description' => 'Table column keys in order, from available.columns plus available.nativeSources[key].columns for a native source or available.customSources.columns for a custom one. [] shows no extra columns; null restores the defaults.',
            ],
            'defaultSort' => [
                'type' => ['array', 'null'],
                'items' => ['type' => 'string'],
                'minItems' => 2,
                'maxItems' => 2,
                'description' => '[attribute, "asc" or "desc"], with an attribute from available.sortOptions plus the source’s own sortOptions under available.nativeSources[key] or available.customSources. null restores the default.',
            ],
            'defaultViewMode' => ['type' => ['string', 'null'], 'description' => 'A mode from available.viewModes. null restores the default.'],
            'condition' => [...ConditionSchema::Schema, 'description' => 'Custom sources only. ' . ConditionSchema::Schema['description']],
            'sites' => [...self::UidListSchema, 'description' => 'Custom sources only. Site UIDs the source shows for; [] for none, null for all.'],
            'userGroups' => [...self::UidListSchema, 'description' => 'Custom sources only. User group UIDs that can see the source; [] for admins only, null for everyone.'],
        ],
        'required' => ['type'],
        'additionalProperties' => true,
    ];

    private const SourcesSchema = [
        'type' => 'array',
        'items' => self::SourceSchema,
        'description' => 'Sources in display order. For element types without pages.',
    ];

    private const PagesSchema = [
        'type' => 'array',
        'description' => 'Pages in nav order, each with its sources in display order. For element types with pages, such as entries.',
        'items' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1],
                'icon' => ['type' => ['string', 'null'], 'description' => 'A Font Awesome icon name, such as newspaper or calendar. Omit to keep the page’s icon; null for the default.'],
                'sources' => ['type' => 'array', 'items' => self::SourceSchema],
            ],
            'required' => ['name', 'sources'],
            'additionalProperties' => true,
        ],
    ];

    /** @var array<string, mixed> State of the element_sources_save call in progress */
    private array $save = [];

    public function __construct(
        private McpActor $actor,
        private GeneralConfig $config,
        private ElementQueryFactory $elementTypes,
        private ElementSourcesService $sources,
        private ProjectConfig $projectConfig,
        private Sites $sites,
        private UserGroups $userGroups,
        private ConditionSchema $conditions,
    ) {
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'element_sources_get',
        description: 'Gets an element type’s index sidebar as one document, as Customize Sources in the control panel shows it: pages with their icons (entries), then headings, native sources, and custom sources in display order. Each source has its columns, default sort, and default view mode; custom sources also have their condition, sites, and user groups. customized is false while Craft’s default sidebar is in use. available lists the columns, sort options, view modes, sites, and user groups element_sources_save accepts, with notes on using them; condition_schema lists the condition rules. Pass revision to element_sources_save as expectedRevision.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        #[Schema(description: 'Element type reference handle, such as entry or asset, its plural, or its PHP class.')]
        string $elementType,
    ): array {
        $this->authorize();
        $type = $this->elementTypes->resolve($elementType);
        $this->sources->refreshSources();

        return $this->document($type);
    }

    /**
     * @param list<array<string, mixed>>|null $pages
     * @param list<array<string, mixed>>|null $sources
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'element_sources_save',
        description: 'Replaces an element type’s whole index sidebar. Send pages when element_sources_get returns multiPage: true (entries), otherwise sources, in display order; usually that document with your changes. An existing source only needs type and key: settings it omits keep their saved values, and null restores the default. Send existing headings and custom sources with their keys. Ones without a key are created, and ones you leave out are deleted. A source belongs to the heading above it. Native sources can’t be removed or renamed; set enabled: false to hide one. Singles share one native source; available.notes explains how to list one on its own. Like the control panel, native sources you leave out are added back at the end of the first page, under a blank heading if the sidebar has headings; addedNativeSources lists them. Renaming a page changes its control panel URL. reset: true discards all customizations and restores Craft’s default sidebar. Returns the saved document and its new revision, without available.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function save(
        #[Schema(description: 'Element type reference handle, such as entry or asset, its plural, or its PHP class.')]
        string $elementType,
        #[Schema(definition: self::PagesSchema)]
        ?array $pages = null,
        #[Schema(definition: self::SourcesSchema)]
        ?array $sources = null,
        #[Schema(definition: ConfigurationSchema::ExpectedRevision)]
        ?string $expectedRevision = null,
        #[Schema(description: 'true discards every customization, restoring Craft’s default sidebar and deleting its custom sources and headings. Omit pages and sources.')]
        bool $reset = false,
    ): array {
        $this->authorize(true);
        $type = $this->elementTypes->resolve($elementType);
        $multiPage = $type::multiPageSources();

        if ($reset) {
            if ($pages !== null || $sources !== null) {
                throw new ToolCallException('Omit pages and sources when resetting.');
            }

            $this->checkRevision($type, $expectedRevision);
            $this->projectConfig->remove(ProjectConfig::PATH_ELEMENT_SOURCES . ".$type");
            $this->projectConfig->remove(ProjectConfig::PATH_ELEMENT_SOURCE_PAGES . ".$type");
            $this->sources->refreshSources();

            return $this->document($type, false);
        }

        if ($multiPage && ($pages === null || $sources !== null)) {
            throw new ToolCallException(sprintf('%s use pages. Supply pages, not sources.', $type::pluralDisplayName()));
        }

        if (!$multiPage && ($sources === null || $pages !== null)) {
            throw new ToolCallException(sprintf('%s don’t use pages. Supply sources, not pages.', $type::pluralDisplayName()));
        }

        $this->sources->refreshSources();
        $this->save = [
            'type' => $type,
            'stored' => ArrayHelper::index(array_filter($this->storedSources($type), fn(array $s) => isset($s['key'])), 'key'),
            'natives' => ArrayHelper::index(array_filter(
                $this->sources->getSources($type, ElementSourcesService::CONTEXT_INDEX, true),
                fn(array $s) => $s['type'] === ElementSourcesService::TYPE_NATIVE,
            ), 'key'),
            'seen' => [],
            'baseSortOptions' => $this->baseSortOptions($type),
            'customSortOptions' => $this->sourceSortOptions($type, 'custom:x'),
            'baseColumns' => array_keys($this->sources->getAvailableTableAttributes($type)),
            'customColumns' => array_keys($this->sources->getSourceTableAttributes($type, 'custom:x')),
        ];
        $configs = [];
        $pageSettings = [];

        if ($multiPage) {
            $pageIds = [];
            $storedPages = $this->sources->getPageSettings($type);

            foreach ($pages as $i => $page) {
                $name = trim((string)($page['name'] ?? ''));
                $nameId = $this->sources->pageNameId($name);

                if ($nameId === '') {
                    throw new ToolCallException("pages[$i].name needs at least one letter or number.");
                }

                if (isset($pageIds[$nameId])) {
                    throw new ToolCallException("pages[$i].name “{$name}” is the same page as “{$pageIds[$nameId]}”. Page names must differ in more than case and punctuation.");
                }

                $pageIds[$nameId] = $name;
                $pageSources = $page['sources'] ?? [];

                if (!ArrayHelper::contains($pageSources, fn($s) => ($s['type'] ?? null) !== ElementSourcesService::TYPE_HEADING)) {
                    throw new ToolCallException("Page “{$name}” needs at least one source. Pages with only headings don’t appear in the control panel.");
                }

                foreach ($pageSources as $j => $source) {
                    $configs[] = $this->sourceConfig($source, "pages[$i].sources[$j]", $name);
                }

                $icon = array_key_exists('icon', $page) ? $page['icon'] : ($storedPages[$name]['icon'] ?? null);
                $pageSettings[$name] = array_filter(['icon' => $icon], fn($value) => $value !== null && $value !== '');
            }
        } else {
            foreach ($sources as $i => $source) {
                $configs[] = $this->sourceConfig($source, "sources[$i]", null);
            }
        }

        $this->checkRevision($type, $expectedRevision);
        $this->sources->saveSources($type, $configs);

        if ($multiPage) {
            $this->sources->savePageSettings($type, $pageSettings);
        }

        $this->sources->refreshSources();
        $added = array_values(array_map(
            fn(array $source) => ['key' => $source['key'], 'label' => $source['label'] ?? ''],
            array_diff_key($this->save['natives'], $this->save['seen']),
        ));

        return [...$this->document($type, false), 'addedNativeSources' => $added];
    }

    /** @param class-string<ElementInterface> $type */
    private function checkRevision(string $type, ?string $expectedRevision): void
    {
        if ($expectedRevision === null) {
            return;
        }

        // Hold the project config lock from this check until the change is saved, so nothing can change the sidebar in between.
        $this->projectConfig->acquireLock();

        if ($this->revision($type) !== $expectedRevision) {
            throw new ToolCallException("The sidebar has changed since revision $expectedRevision was read, so nothing was changed. Read it again with element_sources_get, redo your change on the current values, and pass the new revision.");
        }
    }

    /** @param class-string<ElementInterface> $type */
    private function document(string $type, bool $withAvailable = true): array
    {
        $stored = ArrayHelper::index(array_filter($this->storedSources($type), fn(array $s) => isset($s['key'])), 'key');
        $multiPage = $type::multiPageSources();
        $defaultPage = $multiPage ? $this->defaultPageName($type) : null;
        $pageSettings = $multiPage ? $this->sources->getPageSettings($type) : [];
        $baseSortOptions = $this->baseSortOptions($type);
        $customSortOptions = $this->sourceSortOptions($type, 'custom:x');
        $customColumns = $this->columnOptions($this->sources->getSourceTableAttributes($type, 'custom:x'));
        $items = [];
        $pages = [];
        $available = [];

        foreach ($this->sources->getSources($type, ElementSourcesService::CONTEXT_INDEX, true) as $source) {
            $key = $source['key'] ?? null;

            if ($source['type'] === ElementSourcesService::TYPE_HEADING) {
                $item = array_filter(['type' => 'heading', 'key' => $key], fn($value) => $value !== null) + ['heading' => $source['heading'] ?? ''];
            } else {
                $isCustom = $source['type'] === ElementSourcesService::TYPE_CUSTOM;
                $raw = $stored[$key] ?? [];
                $sortOptions = $isCustom ? $customSortOptions : $this->nativeSortOptions($type, $source);
                $item = [
                    'type' => $source['type'],
                    'key' => $key,
                    'label' => (string)(($isCustom ? $raw['label'] ?? null : null) ?? $source['label'] ?? ''),
                    ...($isCustom ? [] : ['enabled' => !($source['disabled'] ?? false)]),
                    'columns' => array_map(fn(array $attribute) => $attribute[0], array_slice($this->sources->getTableAttributes($type, $key), 1)),
                    // Ordered like Customize Sources, which falls back to the first option
                    'defaultSort' => $this->effectiveDefaultSort($source['defaultSort'] ?? null, [
                        ...array_filter($sortOptions, fn(array $option) => $option['attribute'] === 'structure'),
                        ...$baseSortOptions,
                        ...array_filter($sortOptions, fn(array $option) => $option['attribute'] !== 'structure'),
                    ]),
                    'defaultViewMode' => $source['defaultViewMode'] ?? null,
                ];

                if ($isCustom) {
                    $item += [
                        'condition' => $raw['condition'] ?? null,
                        'sites' => $this->storedUids($raw, 'sites'),
                        'userGroups' => $this->storedUids($raw, 'userGroups'),
                    ];
                } else {
                    $available[$key] = [
                        'columns' => $this->columnOptions($this->sources->getSourceTableAttributes($type, $key)),
                        'sortOptions' => $sortOptions,
                    ];
                }
            }

            if ($multiPage) {
                $pages[$source['page'] ?? $defaultPage][] = $item;
            } else {
                $items[] = $item;
            }
        }

        return [
            'elementType' => $type,
            'multiPage' => $multiPage,
            ...($multiPage
                ? ['pages' => array_map(fn(string $name, array $sources) => [
                    'name' => $name,
                    'icon' => $pageSettings[$name]['icon'] ?? null,
                    'sources' => $sources,
                ], array_map('strval', array_keys($pages)), array_values($pages))]
                : ['sources' => $items]),
            'customized' => $this->storedSources($type) !== [] || $pageSettings !== [],
            'revision' => $this->revision($type),
            ...($withAvailable ? ['available' => $this->available($type, $baseSortOptions, $customSortOptions, $customColumns, $available)] : []),
        ];
    }

    /**
     * @param class-string<ElementInterface> $type
     * @return array<string, mixed>
     */
    private function available(string $type, array $baseSortOptions, array $customSortOptions, array $customColumns, array $nativeSources): array
    {
        return [
            'viewModes' => array_map(fn(array $mode) => array_filter([
                'mode' => $mode['mode'],
                'title' => $mode['title'] ?? null,
                'structuresOnly' => $mode['structuresOnly'] ?? null,
            ], fn($value) => $value !== null), $type::indexViewModes()),
            'columns' => $this->columnOptions($this->sources->getAvailableTableAttributes($type)),
            'sortOptions' => $baseSortOptions,
            'notes' => array_values(array_filter([
                'Every source can use columns and sortOptions. nativeSources adds the extra ones each native source offers, such as its fields (field:<fieldUid>), and customSources the extra ones custom sources offer. The title column always comes first and isn’t listed.',
                'Without a defaultSort, a source sorts by the first of its sort options, as in the control panel. Without columns, it shows the element type’s default columns. Set both on new custom sources.',
                'For the rules custom source conditions accept, call condition_schema with context element-sources and this elementType.',
                isset($nativeSources['singles']) ? 'Singles share the singles source. To list one single on its own, add a custom source with a Section rule.' : null,
            ])),
            'nativeSources' => $nativeSources,
            'customSources' => ['columns' => $customColumns, 'sortOptions' => $customSortOptions],
            'sites' => array_map(fn($site) => ['uid' => $site->uid, 'name' => $site->getName(), 'handle' => $site->handle, 'language' => $site->language], $this->sites->getAllSites(true)),
            'userGroups' => array_map(fn($group) => ['uid' => $group->uid, 'name' => $group->name, 'handle' => $group->handle], $this->userGroups->getAllGroups()),
        ];
    }

    /**
     * Returns a source config as ElementSources::saveSources() stores it, keeping stored settings the input omits.
     *
     * @param mixed $input
     * @return array<string, mixed>
     */
    private function sourceConfig(mixed $input, string $path, ?string $page): array
    {
        if (!is_array($input)) {
            throw new ToolCallException("$path must be an object.");
        }

        $sourceType = $input['type'] ?? null;
        $key = $input['key'] ?? null;
        $key = match ($sourceType) {
            ElementSourcesService::TYPE_HEADING => $key ?? 'heading:' . StringHelper::UUID(),
            ElementSourcesService::TYPE_CUSTOM => $key ?? 'custom:' . StringHelper::UUID(),
            ElementSourcesService::TYPE_NATIVE => $key ?? throw new ToolCallException("$path.key is required for native sources."),
            default => throw new ToolCallException("$path.type must be heading, native, or custom."),
        };

        if (!is_string($key) || $key === '') {
            throw new ToolCallException("$path.key must be a string.");
        }

        $prefix = match (true) {
            str_starts_with($key, 'heading:') => ElementSourcesService::TYPE_HEADING,
            str_starts_with($key, 'custom:') => ElementSourcesService::TYPE_CUSTOM,
            default => ElementSourcesService::TYPE_NATIVE,
        };

        if ($prefix !== $sourceType) {
            throw new ToolCallException("$path.key “{$key}” doesn’t match type $sourceType. Heading keys start with heading:, and custom source keys with custom:.");
        }

        if ($sourceType === ElementSourcesService::TYPE_NATIVE && !isset($this->save['natives'][$key])) {
            throw new ToolCallException("$path.key “{$key}” isn’t a native source of this element type. Read element_sources_get for the current sources.");
        }

        if (isset($this->save['seen'][$key])) {
            throw new ToolCallException("$path.key “{$key}” appears more than once.");
        }

        $this->save['seen'][$key] = true;
        $config = [...($this->save['stored'][$key] ?? []), 'type' => $sourceType, 'key' => $key];
        unset($config['page']);

        if ($page !== null) {
            $config['page'] = $page;
        }

        if ($sourceType === ElementSourcesService::TYPE_HEADING) {
            $config['heading'] = (string)($input['heading'] ?? $config['heading'] ?? '');

            return $config;
        }

        if ($sourceType === ElementSourcesService::TYPE_NATIVE && array_key_exists('enabled', $input)) {
            if (!is_bool($input['enabled'])) {
                throw new ToolCallException("$path.enabled must be true or false.");
            }

            $config['disabled'] = !$input['enabled'];
        }

        if ($sourceType === ElementSourcesService::TYPE_CUSTOM) {
            $config['label'] = trim((string)($input['label'] ?? $config['label'] ?? ''));

            if ($config['label'] === '') {
                throw new ToolCallException("$path.label is required for custom sources.");
            }

            if (array_key_exists('condition', $input) || !isset($config['condition'])) {
                $config['condition'] = $this->conditions->normalize($this->conditions->forElementSources($this->save['type']), $input['condition'] ?? null, "$path.condition");
            }

            foreach (['sites' => $this->siteUids(), 'userGroups' => $this->userGroupUids()] as $setting => $uids) {
                if (array_key_exists($setting, $input)) {
                    $config[$setting] = $this->uidSetting($input[$setting], $uids, "$path.$setting");
                }
            }
        }

        $sortOptions = [
            ...($sourceType === ElementSourcesService::TYPE_CUSTOM
                ? $this->save['customSortOptions']
                : $this->nativeSortOptions($this->save['type'], $this->save['natives'][$key])),
            ...$this->save['baseSortOptions'],
        ];

        if (array_key_exists('columns', $input)) {
            $columns = $input['columns'];
            $allowed = [...$this->save['baseColumns'], ...($sourceType === ElementSourcesService::TYPE_CUSTOM
                ? $this->save['customColumns']
                : array_keys($this->sources->getSourceTableAttributes($this->save['type'], $key)))];

            if ($columns === null) {
                unset($config['tableAttributes']);
            } else {
                $unknown = array_diff($columns, $allowed);

                if ($unknown !== []) {
                    throw new ToolCallException("$path.columns has columns this source doesn’t offer: " . implode(', ', $unknown) . '. Read available in element_sources_get.');
                }

                $config['tableAttributes'] = array_values(array_unique($columns)) ?: '-';
            }
        }

        if (array_key_exists('defaultSort', $input)) {
            $sort = $input['defaultSort'];

            if ($sort === null) {
                unset($config['defaultSort']);
            } else {
                [$attribute, $direction] = $sort;

                if (!in_array($direction, ['asc', 'desc'], true)) {
                    throw new ToolCallException("$path.defaultSort direction must be asc or desc.");
                }

                if (!ArrayHelper::contains($sortOptions, fn(array $option) => $option['attribute'] === $attribute)) {
                    throw new ToolCallException("$path.defaultSort attribute “{$attribute}” isn’t a sort option of this source. Read available in element_sources_get.");
                }

                $config['defaultSort'] = [$attribute, $direction];
            }
        }

        if (array_key_exists('defaultViewMode', $input)) {
            $mode = $input['defaultViewMode'];

            if ($mode === null) {
                unset($config['defaultViewMode']);
            } elseif (!in_array($mode, array_column($this->save['type']::indexViewModes(), 'mode'), true)) {
                throw new ToolCallException("$path.defaultViewMode “{$mode}” isn’t a view mode of this element type.");
            } else {
                $config['defaultViewMode'] = $mode;
            }
        }

        return $config;
    }

    /**
     * @param class-string<ElementInterface> $type
     * @return list<array{attribute: string, label: string, defaultDir: string}>
     */
    private function baseSortOptions(string $type): array
    {
        $options = [];

        foreach ($type::sortOptions() as $key => $option) {
            $attribute = is_array($option) ? ($option['attribute'] ?? $option['orderBy'] ?? $key) : $key;

            if (is_string($attribute)) {
                $options[] = [
                    'attribute' => $attribute,
                    'label' => (string)(is_array($option) ? $option['label'] : $option),
                    'defaultDir' => is_array($option) ? ($option['defaultDir'] ?? 'asc') : 'asc',
                ];
            }
        }

        return $options;
    }

    /**
     * @param class-string<ElementInterface> $type
     * @return list<array{attribute: string, label: string, defaultDir: string}>
     */
    private function sourceSortOptions(string $type, string $key): array
    {
        $options = [];

        foreach ($this->sources->getSourceSortOptions($type, $key) as $option) {
            $attribute = $option['attribute'] ?? $option['orderBy'];

            if (is_string($attribute)) {
                $options[] = ['attribute' => $attribute, 'label' => (string)$option['label'], 'defaultDir' => $option['defaultDir'] ?? 'asc'];
            }
        }

        return $options;
    }

    /**
     * @param class-string<ElementInterface> $type
     * @param array<string, mixed> $source
     * @return list<array{attribute: string, label: string, defaultDir: string}>
     */
    private function nativeSortOptions(string $type, array $source): array
    {
        return [
            ...(isset($source['structureId']) ? [['attribute' => 'structure', 'label' => Craft::t('app', 'Structure'), 'defaultDir' => 'asc']] : []),
            ...$this->sourceSortOptions($type, $source['key']),
        ];
    }

    /**
     * Resolves a stored default sort to the [attribute, direction] the element index uses, as Customize Sources does.
     *
     * @param list<array{attribute: string, label: string, defaultDir: string}> $options
     * @return array{0: string, 1: string}|null
     */
    private function effectiveDefaultSort(mixed $stored, array $options): ?array
    {
        [$attribute, $direction] = is_array($stored) ? [$stored[0] ?? null, $stored[1] ?? null] : [$stored, null];
        $option = ArrayHelper::firstWhere($options, 'attribute', $attribute) ?? reset($options) ?: null;

        return $option ? [$option['attribute'], $direction ?? $option['defaultDir']] : null;
    }

    /**
     * @param array<string, array{label: string}> $attributes
     * @return list<array{key: string, label: string}>
     */
    private function columnOptions(array $attributes): array
    {
        return array_map(fn(string $key, array $info) => ['key' => $key, 'label' => (string)($info['label'] ?? '')], array_map('strval', array_keys($attributes)), array_values($attributes));
    }

    /** @return list<string>|null */
    private function storedUids(array $source, string $setting): ?array
    {
        if (!array_key_exists($setting, $source)) {
            return null;
        }

        return is_array($source[$setting]) ? array_values($source[$setting]) : [];
    }

    /**
     * Returns a sites or userGroups setting as saveSources() stores it: null for all, false for none, or UIDs.
     *
     * @param list<string> $known
     * @return list<string>|false|null
     */
    private function uidSetting(mixed $value, array $known, string $path): array|false|null
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new ToolCallException("$path must be a list of UIDs or null.");
        }

        $unknown = array_diff($value, $known);

        if ($unknown !== []) {
            throw new ToolCallException("$path has unknown UIDs: " . implode(', ', $unknown) . '. Read available in element_sources_get.');
        }

        return array_values(array_unique($value)) ?: false;
    }

    /** @return list<string> */
    private function siteUids(): array
    {
        return array_map(fn($site) => $site->uid, $this->sites->getAllSites(true));
    }

    /** @return list<string> */
    private function userGroupUids(): array
    {
        return array_map(fn($group) => $group->uid, $this->userGroups->getAllGroups());
    }

    /** @param class-string<ElementInterface> $type */
    private function defaultPageName(string $type): string
    {
        // Customize Sources names the default page with the untranslated plural name
        $language = Craft::$app->language;
        Craft::$app->language = Craft::$app->sourceLanguage;

        try {
            return $type::pluralDisplayName();
        } finally {
            Craft::$app->language = $language;
        }
    }

    /**
     * @param class-string<ElementInterface> $type
     * @return list<array<string, mixed>>
     */
    private function storedSources(string $type): array
    {
        return $this->projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES . ".$type") ?? [];
    }

    /** An opaque revision of the element type’s saved sources and page settings. */
    private function revision(string $type): string
    {
        $paths = [ProjectConfig::PATH_ELEMENT_SOURCES . ".$type", ProjectConfig::PATH_ELEMENT_SOURCE_PAGES . ".$type"];
        // Saved config is reloaded with sorted keys and without empty values, so normalize it the same way first
        $config = ProjectConfigHelper::cleanupConfig(array_combine($paths, array_map(fn(string $path) => $this->projectConfig->get($path), $paths)));

        return substr(hash('sha256', ProjectConfigHelper::encodeValueAsString(array_map(fn(string $path) => $config[$path] ?? null, $paths))), 0, 32);
    }

    private function authorize(bool $write = false): void
    {
        if (!$this->actor->user()->admin) {
            throw new ToolCallException('Admin access is required to customize sources.');
        }

        if ($write && !$this->config->allowAdminChanges) {
            throw new ToolCallException('Admin changes are disabled.');
        }
    }
}
