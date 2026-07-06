<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

#[Scoped]
class ElementIndexes
{
    public function __construct(
        private readonly ElementSources $elementSources,
    ) {}

    /**
     * Returns the data needed to render an element index screen for the given element type.
     *
     * Returns null if the requested source doesn't exist (or no sources are available), so
     * callers can redirect to a valid source page.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function indexData(
        string $elementType,
        ?string $page = null,
        ?string $sourceKey = null,
        ?string $search = null,
        ?string $sortAttribute = null,
        string $sortDirection = 'asc',
        ?int $siteId = null,
        ?string $status = null,
        int $pageNum = 1,
        int $perPage = 100,
    ): ?array {
        $sources = $this->elementSources->getSources($elementType, page: $page)
            ->reject(fn (array $source) => ($source['sites'] ?? null) === []);

        $selectableSources = $sources->reject(
            fn (array $source) => ($source['type'] ?? null) === ElementSources::TYPE_HEADING,
        )->values();

        if ($selectableSources->isEmpty()) {
            return null;
        }

        $source = $sourceKey !== null
            ? $selectableSources->firstWhere('key', $sourceKey)
            : $selectableSources->first();

        if ($source === null) {
            return null;
        }

        $sourceKey = $source['key'];

        $columns = $this->columns($elementType, $sourceKey);
        $sortOptions = $this->sortOptions($elementType, $sourceKey);
        $sortableAttributes = array_column($sortOptions, 'attribute');

        if ($sortAttribute === null || ! in_array($sortAttribute, $sortableAttributes, true)) {
            [$sortAttribute, $sortDirection] = $this->defaultSort($source);
        }

        if ($status !== null && ! array_key_exists($status, $elementType::statuses())) {
            $status = null;
        }

        $query = $this->buildQuery($elementType, $source, $search, $siteId, $status);

        if ($sortAttribute !== null && $sortAttribute !== 'structure') {
            $elementType::applyIndexSort($query, $sourceKey, $sortAttribute, $sortDirection);
        }

        $total = $elementType::indexElementCount(clone $query, $sourceKey);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $pageNum = min(max(1, $pageNum), $lastPage);

        $query->offset(($pageNum - 1) * $perPage)->limit($perPage);
        $elements = $query->all();

        $paginator = new LengthAwarePaginator(
            items: $elements,
            total: $total,
            perPage: $perPage,
            currentPage: $pageNum,
            options: [
                'path' => request()->url(),
                'pageName' => Cms::config()->getPageTriggerParam(),
            ],
        );
        $paginator->appends(request()->query());

        return [
            'sources' => $this->serializeSources($sources, $siteId),
            'selectedSource' => $sourceKey,
            'columns' => $columns,
            'sortOptions' => $sortOptions,
            'sort' => [['field' => $sortAttribute ?? '', 'direction' => $sortDirection]],
            'elements' => $this->serializeElements($elements, $columns),
            'pagination' => Arr::only($paginator->toArray(), [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'from',
                'to',
            ]),
        ];
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function buildQuery(
        string $elementType,
        array $source,
        ?string $search,
        ?int $siteId,
        ?string $status,
    ): ElementQueryInterface {
        $query = $elementType::find();

        if ($source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($source['condition']);
            $sourceCondition->modifyQuery($query);
        }

        if (! empty($source['criteria'])) {
            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($source['criteria']));
        }

        if ($siteId !== null) {
            $query->siteId($siteId);
        }

        if ($status !== null) {
            $query->status($status);
        }

        if ($search !== null && $search !== '') {
            $query->search($search);
        }

        return $query;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function columns(string $elementType, string $sourceKey): array
    {
        return $this->elementSources
            ->getTableAttributes($elementType, $sourceKey)
            ->map(fn (array $attribute) => [
                'key' => $attribute[0],
                'label' => $attribute[1]['label'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function sortOptions(string $elementType, string $sourceKey): array
    {
        $options = [];

        foreach ($elementType::sortOptions() as $key => $option) {
            if (! is_array($option)) {
                $options[$key] = [
                    'label' => $option,
                    'attribute' => $key,
                    'defaultDir' => 'asc',
                ];

                continue;
            }

            $attribute = $option['attribute']
                ?? (is_string($option['orderBy'] ?? null) ? $option['orderBy'] : null);

            if ($attribute !== null) {
                $options[$attribute] = [
                    'label' => $option['label'] ?? $attribute,
                    'attribute' => $attribute,
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ];
            }
        }

        foreach ($this->elementSources->getSourceSortOptions($elementType, $sourceKey) as $option) {
            $attribute = $option['attribute']
                ?? (is_string($option['orderBy'] ?? null) ? $option['orderBy'] : null);

            if ($attribute !== null && ! isset($options[$attribute])) {
                $options[$attribute] = [
                    'label' => $option['label'] ?? $attribute,
                    'attribute' => $attribute,
                    'defaultDir' => $option['defaultDir'] ?? 'asc',
                ];
            }
        }

        return array_values($options);
    }

    /**
     * @return array{0:?string,1:string}
     */
    private function defaultSort(array $source): array
    {
        $defaultSort = $source['defaultSort'] ?? null;

        if (is_string($defaultSort)) {
            return [$defaultSort, 'asc'];
        }

        if (is_array($defaultSort) && isset($defaultSort[0])) {
            return [$defaultSort[0], strcasecmp($defaultSort[1] ?? 'asc', 'desc') === 0 ? 'desc' : 'asc'];
        }

        return [null, 'asc'];
    }

    private function serializeSources(Collection $sources, ?int $siteId): array
    {
        $serialized = [];

        foreach ($sources as $source) {
            if (($source['type'] ?? null) === ElementSources::TYPE_HEADING) {
                $serialized[] = [
                    'type' => ElementSources::TYPE_HEADING,
                    'heading' => $source['heading'] ?? '',
                ];

                continue;
            }

            if (
                $siteId !== null &&
                isset($source['sites']) &&
                ! in_array($siteId, $source['sites'], true)
            ) {
                continue;
            }

            $serialized[] = [
                'type' => $source['type'],
                'key' => $source['key'],
                'label' => $source['label'] ?? '',
                'badgeCount' => $source['badgeCount'] ?? null,
                'nested' => isset($source['nested'])
                    ? $this->serializeSources(collect($source['nested']), $siteId)
                    : [],
            ];
        }

        return $serialized;
    }

    /**
     * @param  Element[]  $elements
     */
    private function serializeElements(array $elements, array $columns): array
    {
        return array_map(function (Element $element) use ($columns): array {
            $attributeHtml = [];

            foreach ($columns as $column) {
                if ($column['key'] !== 'title') {
                    $attributeHtml[$column['key']] = $element->getAttributeHtml($column['key']);
                }
            }

            return [
                'id' => $element->id,
                'title' => $element->getUiLabel(),
                'url' => $element->getCpEditUrl(),
                'status' => $element->getStatus(),
                'attributeHtml' => $attributeHtml,
            ];
        }, $elements);
    }
}
