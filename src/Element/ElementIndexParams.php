<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;

/**
 * Holds all resolved inputs needed to query and render an element index.
 */
readonly class ElementIndexParams
{
    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  FieldLayout[]|null  $fieldLayouts
     */
    public function __construct(
        public string $elementType,
        public ?string $sourceKey = null,
        public ?array $source = null,
        public ?ElementConditionInterface $condition = null,
        public array $viewState = ['mode' => 'table'],
        public string $context = ElementSources::CONTEXT_INDEX,
        public array $disabledElementIds = [],
        public ?string $returnUrl = null,
        public array $baseCriteria = [],
        public array $criteria = [],
        public ?array $filterConfig = null,
        public ?array $collapsedElementIds = null,
        public bool $isAdministrative = true,
        public bool $selectable = false,
        public bool $sortable = false,
        public bool $includeContainer = true,
        public bool $includeActions = true,
        public ?array $fieldLayouts = null,
        public int $page = 1,
        public int $perPage = 100,
        public array $sort = [],
    ) {}

    /**
     * Build params from an ElementIndexRequest (for legacy action endpoints).
     */
    public static function fromRequest(
        ElementIndexRequest $request,
        ElementSources $elementSources,
        bool $includeContainer = true,
        bool $includeActions = true,
    ): self {
        $elementType = $request->elementType();
        $context = $request->context();
        $sourceKey = $request->input('source');

        [$resolvedSourceKey, $source] = self::resolveSourceFromKey($elementType, $sourceKey, $context, $elementSources);

        $fieldLayouts = self::resolveFieldLayoutsFromRequest($request);

        $viewState = $request->input('viewState', []);
        if (empty($viewState['mode'])) {
            $viewState['mode'] = 'table';
        }

        $filterConfig = $request->input('filterConfig');
        if (! $filterConfig && $filterStr = $request->input('filters')) {
            parse_str((string) $filterStr, $filterConfig);
            $filterConfig = $filterConfig['condition'] ?? null;
        }

        $returnUrl = $request->input('returnUrl');
        if ($returnUrl) {
            $returnUrl = str_replace('?', ':QS:', $returnUrl);
        }

        return new self(
            elementType: $elementType,
            sourceKey: $resolvedSourceKey,
            source: $source,
            condition: $request->condition(),
            viewState: $viewState,
            context: $context,
            disabledElementIds: $request->array('disabledElementIds'),
            returnUrl: $returnUrl,
            baseCriteria: $request->input('baseCriteria') ?? [],
            criteria: $request->input('criteria') ?? [],
            filterConfig: $filterConfig,
            collapsedElementIds: $request->input('collapsedElementIds'),
            isAdministrative: $request->isAdministrative(),
            selectable: $request->boolean('selectable'),
            sortable: $request->isAdministrative() && $request->boolean('sortable'),
            includeContainer: $includeContainer,
            includeActions: $includeActions,
            fieldLayouts: $fieldLayouts,
        );
    }

    /**
     * Build params from known context (for ContentIndexController / Inertia pages).
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public static function fromContext(
        string $elementType,
        ?string $sourceKey,
        string $context = ElementSources::CONTEXT_INDEX,
        ?ElementSources $elementSources = null,
        array $criteria = [],
        array $baseCriteria = [],
        int $page = 1,
        int $perPage = 100,
        array $sort = [],
    ): self {
        $source = null;

        if ($sourceKey !== null && $elementSources !== null) {
            [$sourceKey, $source] = self::resolveSourceFromKey($elementType, $sourceKey, $context, $elementSources);
        }

        return new self(
            elementType: $elementType,
            sourceKey: $sourceKey,
            source: $source,
            context: $context,
            baseCriteria: $baseCriteria,
            criteria: $criteria,
            isAdministrative: in_array($context, [ElementSources::CONTEXT_INDEX, ElementSources::CONTEXT_EMBEDDED_INDEX]),
            page: $page,
            perPage: $perPage,
            sort: $sort,
        );
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array{0: ?string, 1: ?array}
     */
    private static function resolveSourceFromKey(
        string $elementType,
        ?string $sourceKey,
        string $context,
        ElementSources $elementSources,
    ): array {
        if ($sourceKey === null) {
            return [null, null];
        }

        if ($sourceKey === '__IMP__') {
            return [
                $sourceKey, [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => '__IMP__',
                    'label' => \CraftCms\Cms\t('All elements'),
                    'hasThumbs' => $elementType::hasThumbs(),
                ],
            ];
        }

        $source = $elementSources->findSource($elementType, $sourceKey, $context);

        if ($source === null) {
            $sourceKey = null;
        }

        return [$sourceKey, $source];
    }

    /**
     * @return FieldLayout[]|null
     */
    private static function resolveFieldLayoutsFromRequest(ElementIndexRequest $request): ?array
    {
        $fieldLayouts = $request->input('fieldLayouts');

        if (empty($fieldLayouts) || ! is_array($fieldLayouts)) {
            return null;
        }

        return array_map(
            FieldLayout::createFromConfig(...),
            $fieldLayouts,
        );
    }
}
