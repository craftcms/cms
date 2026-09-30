<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Facades\ElementSources;

use function CraftCms\Cms\t;

readonly class ElementIndexSourceSettings
{
    public const string NESTED_KEY = '__IMP__';

    /**
     * @param  array<string, mixed>|null  $source
     * @param  list<array<string, mixed>>  $viewModes
     * @param  list<array{label: string, value: string, defaultDir: string, fixedDir?: string}>  $sortOptions
     * @param  list<array{label: string, value: string}>  $tableColumns
     * @param  list<string>  $defaultTableColumns
     * @param  list<array{field: string, direction: string}>  $defaultSort
     * @param  list<FieldLayout>|null  $fieldLayouts
     * @param  list<string>  $preserveQueryOrderFor
     * @param  array<string, mixed>  $criteria
     */
    public function __construct(
        public ?string $sourceKey,
        public ?array $source,
        public int $siteId,
        public array $viewModes,
        public array $sortOptions,
        public array $tableColumns,
        public array $defaultTableColumns,
        public array $defaultSort,
        public string $defaultViewMode,
        public int $pageSize,
        public bool $showHeaderColumn,
        public ?array $fieldLayouts,
        public bool $actionsEnabled = true,
        public array $preserveQueryOrderFor = [],
        public ?ElementInterface $owner = null,
        public ?string $attribute = null,
        public array $criteria = [],
        public bool $static = false,
        public bool $sortable = false,
        public bool $canPaste = false,
        public bool $prevalidate = false,
        public ?int $fieldId = null,
        public ?int $maxElements = null,
        public ?string $storageKey = null,
    ) {}

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $criteria
     * @param  array<string, mixed>  $config
     */
    public static function forNestedOwner(
        string $elementType,
        ElementInterface $owner,
        string $attribute,
        array $criteria,
        array $config,
    ): self {
        $fieldLayouts = array_values(array_filter(
            $config['fieldLayouts'] ?? [],
            fn (mixed $layout): bool => $layout instanceof FieldLayout,
        ));
        $allowedViewModes = array_map(
            fn (mixed $mode): string => $mode instanceof ElementIndexViewMode ? $mode->value : (string) $mode,
            $config['allowedViewModes'] ?? [],
        );
        $defaultTableColumns = array_values(array_filter(array_map(
            fn (mixed $column): mixed => is_array($column) ? ($column[0] ?? null) : $column,
            $config['defaultTableColumns'] ?? [],
        ), is_string(...)));
        $indexState = app(ElementIndexState::class);
        $sortOptions = [];

        foreach ($indexState->sortOptions($elementType) as $option) {
            self::addSortOption($sortOptions, $option);
        }

        $sourceSortOptions = [
            'sortOrder' => [
                'label' => t('Order'),
                'attribute' => 'sortOrder',
                'defaultDir' => 'asc',
                'fixedDir' => 'asc',
            ],
            ...ElementSources::getSourceSortOptions($elementType, self::NESTED_KEY, $fieldLayouts)->all(),
        ];

        foreach ($sourceSortOptions as $key => $option) {
            self::addSortOption($sortOptions, $indexState->normalizeSortOption($option, $key));
        }

        $defaultSort = is_array($config['defaultSort'] ?? null) && is_string($config['defaultSort'][0] ?? null)
            ? [[
                'field' => $config['defaultSort'][0],
                'direction' => ($config['defaultSort'][1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ]]
            : [['field' => 'sortOrder', 'direction' => 'asc']];
        $static = (bool) ($config['static'] ?? false);

        return new self(
            sourceKey: self::NESTED_KEY,
            source: [
                'type' => ElementSources::TYPE_NATIVE,
                'key' => self::NESTED_KEY,
                'label' => t('All elements'),
                'hasThumbs' => $elementType::hasThumbs(),
            ],
            siteId: (int) $criteria['siteId'],
            viewModes: array_values(array_filter(
                $elementType::indexViewModes(),
                fn (array $mode): bool => $allowedViewModes === [] || in_array($mode['mode'], $allowedViewModes, true),
            )),
            sortOptions: array_values($sortOptions),
            tableColumns: $indexState
                ->tableColumns($elementType)
                ->merge(ElementSources::getTableAttributesForFieldLayouts(collect($fieldLayouts)))
                ->map(fn (array $column, string $key): array => ['label' => $column['label'], 'value' => $key])
                ->values()
                ->all(),
            defaultTableColumns: $defaultTableColumns,
            defaultSort: $defaultSort,
            defaultViewMode: (string) ($config['defaultViewMode'] ?? ElementIndexViewMode::Cards->value),
            pageSize: max(1, (int) ($config['pageSize'] ?? 50)),
            showHeaderColumn: (bool) ($config['showHeaderColumn'] ?? true),
            fieldLayouts: $fieldLayouts,
            actionsEnabled: ! $static,
            preserveQueryOrderFor: ['sortOrder'],
            owner: $owner,
            attribute: $attribute,
            criteria: $criteria,
            static: $static,
            sortable: (bool) ($config['sortable'] ?? false),
            canPaste: (bool) ($config['canPaste'] ?? false),
            prevalidate: (bool) ($config['prevalidate'] ?? false),
            fieldId: isset($config['fieldId']) ? (int) $config['fieldId'] : null,
            maxElements: isset($config['maxElements']) ? (int) $config['maxElements'] : null,
            storageKey: isset($config['storageKey']) ? (string) $config['storageKey'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function viewState(): array
    {
        return [
            'showHeaderColumn' => $this->showHeaderColumn,
            ...($this->owner !== null
                ? ['static' => $this->static]
                : ($this->fieldLayouts !== null ? ['fieldLayouts' => $this->fieldLayouts] : [])),
        ];
    }

    /** @return array<string, mixed> */
    public function indexViewState(): array
    {
        return [
            'fieldLayouts' => $this->fieldLayouts,
            ...($this->owner !== null ? ['prevalidate' => $this->prevalidate] : []),
        ];
    }

    /** @return list<array{label: string, value: string, defaultDir: string}> */
    public function publicSortOptions(): array
    {
        return array_map(fn (array $option): array => [
            'label' => $option['label'],
            'value' => $option['value'],
            'defaultDir' => $option['defaultDir'],
        ], $this->sortOptions);
    }

    /**
     * @param  list<array{field: string, direction: string}>  $requestedSort
     * @return list<array{field: string, direction: string}>
     */
    public function sort(array $requestedSort): array
    {
        $sort = $requestedSort ?: $this->defaultSort;
        $fixedDirections = [];

        foreach ($this->sortOptions as $option) {
            if (isset($option['fixedDir'])) {
                $fixedDirections[$option['value']] = $option['fixedDir'];
            }
        }

        return array_map(
            fn (array $item): array => isset($fixedDirections[$item['field']])
                ? [...$item, 'direction' => $fixedDirections[$item['field']]]
                : $item,
            $sort,
        );
    }

    public function shouldResetQueryOrder(string $order): bool
    {
        return ! in_array($order, $this->preserveQueryOrderFor, true);
    }

    /**
     * @param  array<string, array{label: string, value: string, defaultDir: string, fixedDir?: string}>  $options
     * @param  array{attribute: mixed, defaultDir: mixed, label: mixed, option: mixed, ...}  $option
     */
    private static function addSortOption(array &$options, array $option): void
    {
        $attribute = $option['attribute'];

        if (! is_string($attribute) || $attribute === '' || isset($options[$attribute])) {
            return;
        }

        $rawOption = is_array($option['option']) ? $option['option'] : [];
        $fixedDirection = $rawOption['fixedDir'] ?? null;
        $options[$attribute] = [
            'label' => is_string($option['label']) ? $option['label'] : $attribute,
            'value' => $attribute,
            'defaultDir' => $option['defaultDir'] === 'desc' ? 'desc' : 'asc',
            ...(in_array($fixedDirection, ['asc', 'desc'], true) ? ['fixedDir' => $fixedDirection] : []),
        ];
    }
}
