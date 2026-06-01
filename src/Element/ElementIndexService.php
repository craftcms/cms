<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ExcludeDescendantIdsExpression;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\Support\Facades\ElementActions;
use CraftCms\Cms\Support\Facades\ElementExporters;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Typecast;

use function CraftCms\Cms\t;

class ElementIndexService
{
    public function __construct(
        private readonly CurrentElementIndex $currentElementIndex,
        private readonly ElementSources $elementSources,
    ) {}

    /**
     * Build the element query from params.
     *
     * @return array{query: ElementQueryInterface, unfilteredQuery: ElementQueryInterface|null}
     */
    public function buildQueryState(ElementIndexParams $params): array
    {
        $query = $params->elementType::find();

        if (! $params->source) {
            $query->id(false);

            return [
                'query' => $query,
                'unfilteredQuery' => null,
            ];
        }

        if ($params->source['type'] === ElementSources::TYPE_CUSTOM) {
            /** @var ElementConditionInterface $sourceCondition */
            $sourceCondition = Conditions::createCondition($params->source['condition']);
            $sourceCondition->modifyQuery($query);
        }

        $applyCriteria = function (array $criteria) use ($query): bool {
            if (! $criteria) {
                return false;
            }

            if (isset($criteria['trashed'])) {
                $criteria['trashed'] = filter_var($criteria['trashed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['drafts'])) {
                $criteria['drafts'] = filter_var($criteria['drafts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (isset($criteria['draftOf'])) {
                if (is_numeric($criteria['draftOf']) && $criteria['draftOf'] != 0) {
                    $criteria['draftOf'] = (int) $criteria['draftOf'];
                } else {
                    $criteria['draftOf'] = filter_var($criteria['draftOf'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }
            }

            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($criteria));

            return true;
        };

        $applyCriteria($params->baseCriteria);

        $unfilteredQuery = clone $query;
        $hasFilters = false;

        if ($params->condition) {
            $params->condition->modifyQuery($query);
            $hasFilters = true;
        }

        if ($applyCriteria($params->criteria)) {
            $hasFilters = true;
        }

        if ($params->filterConfig) {
            /** @var ElementConditionInterface $filterCondition */
            $filterCondition = Conditions::createCondition($params->filterConfig);
            $filterCondition->modifyQuery($query);
            $hasFilters = true;
        }

        if (! $params->collapsedElementIds) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $descendantQuery = (clone $query)
            ->offset(null)
            ->limit(null)
            ->reorder()
            ->positionedAfter(null)
            ->positionedBefore(null)
            ->status(null);

        $collapsedElements = (clone $descendantQuery)
            ->id($params->collapsedElementIds)
            ->orderBy('lft')
            ->all();

        if (empty($collapsedElements)) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $descendantIds = [];

        foreach ($collapsedElements as $element) {
            if (in_array($element->id, $descendantIds, false)) {
                continue;
            }

            $elementDescendantIds = (clone $descendantQuery)
                ->descendantOf($element)
                ->ids();

            $descendantIds = array_merge($descendantIds, $elementDescendantIds);
        }

        if (empty($descendantIds)) {
            return [
                'query' => $query,
                'unfilteredQuery' => $hasFilters ? $unfilteredQuery : null,
            ];
        }

        $query->where(new ExcludeDescendantIdsExpression($descendantIds));

        return [
            'query' => $query,
            'unfilteredQuery' => $unfilteredQuery,
        ];
    }

    /**
     * Get elements as HTML response data (legacy format for action endpoints).
     *
     * @return array<string, mixed>
     */
    public function getElementsHtml(ElementIndexParams $params): array
    {
        $elementQuery = $this->buildQueryState($params)['query'];

        $this->currentElementIndex->activate($elementQuery);

        $responseData = [];
        $actions = null;
        $exporters = null;

        if ($params->includeActions) {
            if ($params->isAdministrative && isset($params->sourceKey)) {
                $actions = ElementActions::availableActions($params->elementType, $params->sourceKey, $elementQuery);
                $exporters = $this->availableExporters($params->elementType, $params->sourceKey);
            }

            $responseData['actions'] = match (true) {
                ($params->viewState['static'] ?? false) === true => [],
                empty($actions) => null,
                default => ElementActions::serializeActions($actions),
            };

            $responseData['actionsHeadHtml'] = HtmlStack::headHtml();
            $responseData['actionsBodyHtml'] = HtmlStack::bodyHtml();
            $responseData['exporters'] = empty($exporters) ? null : ElementExporters::serializeExporters($exporters);
        }

        if (! $params->sourceKey) {
            $responseData['html'] = Html::tag('craft-empty', attributes: ['label' => t('Nothing yet.')]);

            return $responseData;
        }

        $responseData['html'] = $params->elementType::indexHtml(
            elementQuery: $elementQuery,
            disabledElementIds: $params->disabledElementIds,
            viewState: [
                ...$params->viewState,
                'fieldLayouts' => $params->fieldLayouts,
                'returnUrl' => $params->returnUrl,
            ],
            sourceKey: $params->sourceKey,
            context: $params->context,
            includeContainer: $params->includeContainer,
            selectable: (
                ((! empty($actions)) || $params->selectable) &&
                empty($params->viewState['inlineEditing'])
            ),
            sortable: $params->sortable,
        );

        $responseData['headHtml'] = HtmlStack::headHtml();
        $responseData['bodyHtml'] = HtmlStack::bodyHtml();

        return $responseData;
    }

    /**
     * Get elements as structured JSON data (for Inertia/Vue rendering).
     *
     * @return array<string, mixed>
     */
    public function getElementsJson(ElementIndexParams $params): array
    {
        $elementQuery = $this->buildQueryState($params)['query'];

        $this->currentElementIndex->activate($elementQuery);

        // Apply sorting
        if (! empty($params->sort)) {
            $orderBy = [];
            foreach ($params->sort as $sortItem) {
                $field = $sortItem['field'] ?? null;
                $direction = ($sortItem['direction'] ?? 'asc') === 'desc' ? SORT_DESC : SORT_ASC;
                if ($field) {
                    $orderBy[$field] = $direction;
                }
            }
            if (! empty($orderBy)) {
                $elementQuery->orderBy($orderBy);
            }
        }

        // Get total count before applying pagination
        $total = (clone $elementQuery)->count();

        // Apply pagination
        $perPage = $params->perPage;
        $page = max(1, $params->page);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $elements = $elementQuery->offset($offset)->limit($perPage)->all();

        $from = $total > 0 ? $offset + 1 : 0;
        $to = $total > 0 ? min($offset + $perPage, $total) : 0;

        $responseData = [
            'elements' => array_map(
                fn (ElementInterface $element) => $this->serializeElement($element, $params),
                $elements,
            ),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'next_page_url' => $page < $lastPage ? $this->buildPageUrl($page + 1, $perPage) : null,
                'prev_page_url' => $page > 1 ? $this->buildPageUrl($page - 1, $perPage) : null,
                'from' => $from,
                'to' => $to,
            ],
        ];

        if ($params->includeActions && $params->isAdministrative && isset($params->sourceKey)) {
            $actions = ElementActions::availableActions($params->elementType, $params->sourceKey, $elementQuery);
            $responseData['actions'] = empty($actions) ? null : ElementActions::serializeActions($actions);

            $exporters = $this->availableExporters($params->elementType, $params->sourceKey);
            $responseData['exporters'] = empty($exporters) ? null : ElementExporters::serializeExporters($exporters);
        }

        return $responseData;
    }

    private function buildPageUrl(int $page, int $perPage): string
    {
        $currentUrl = request()->url();
        $query = request()->query();
        $query['page'] = $page;
        $query['per_page'] = $perPage;

        return $currentUrl.'?'.http_build_query($query);
    }

    /**
     * Serialize a single element to an array for JSON output.
     *
     * @return array<string, mixed>
     */
    protected function serializeElement(ElementInterface $element, ElementIndexParams $params): array
    {
        $data = [
            'id' => $element->id,
            'title' => $element->title,
            'slug' => $element->slug,
            'uri' => $element->uri,
            'url' => $element->getUrl(),
            'status' => $element->getStatus(),
            'enabled' => $element->enabled,
            'cpEditUrl' => $element->getCpEditUrl(),
            'dateCreated' => $element->dateCreated?->format('c'),
            'dateUpdated' => $element->dateUpdated?->format('c'),
        ];

        // Include table attribute values if a source is selected
        if ($params->sourceKey) {
            $attributes = $this->elementSources->getTableAttributes(
                elementType: $params->elementType,
                sourceKey: $params->sourceKey,
                customAttributes: $params->viewState['tableColumns'] ?? null,
                fieldLayouts: $params->fieldLayouts,
            );

            $attributeValues = [];
            foreach ($attributes as [$attribute]) {
                $attributeValues[$attribute] = $element->getAttributeHtml($attribute);
            }
            $data['attributes'] = $attributeValues;
        }

        return $data;
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private function availableExporters(string $elementType, string $sourceKey): ?array
    {
        if (request()->isMobileBrowser()) {
            return null;
        }

        return ElementExporters::availableExporters($elementType, $sourceKey);
    }
}
