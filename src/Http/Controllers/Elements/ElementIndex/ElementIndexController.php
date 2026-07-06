<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;

class ElementIndexController
{
    public function __construct(
        private readonly Conditions $conditions,
        private readonly ElementSources $elementSources,
        private readonly ElementIndexes $elementIndexes,
    ) {}

    public function getElements(): ElementIndexResource
    {
        return new ElementIndexResource;
    }

    public function getMoreElements(): ElementIndexResource
    {
        return new ElementIndexResource(
            includeContainer: false,
            includeActions: false,
        );
    }

    public function countElements(ElementIndexRequest $request): JsonResponse
    {
        $elementType = $request->elementType();
        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $request->input('source'), $request->context());
        $elementQueryState = $this->elementIndexes->buildQueryState(
            elementType: $elementType,
            source: $source,
            condition: $request->condition(),
            baseCriteria: $request->baseCriteria(),
            criteria: $request->criteria(),
            filterConditionConfig: $request->filterConditionConfig(),
            collapsedElementIds: $request->collapsedElementIds(),
        );

        $total = $elementType::indexElementCount($elementQueryState['query'], $sourceKey);
        $unfilteredTotal = $elementQueryState['unfilteredQuery']
            ? $elementType::indexElementCount($elementQueryState['unfilteredQuery'], $sourceKey)
            : $total;

        return new JsonResponse([
            'resultSet' => $request->input('resultSet'),
            'total' => $total,
            'unfilteredTotal' => $unfilteredTotal,
        ]);
    }

    public function filterHud(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $elementType = $request->elementType();
        $context = $request->context();
        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $request->input('source.key'), $context);
        $fieldLayouts = $request->fieldLayouts();
        $currentCondition = $request->condition();
        $id = $request->input('id');

        abort_if($id === null || $id === '', 400, 'Request missing required body param');

        $conditionConfig = $request->input('conditionConfig');
        $serialized = $request->input('serialized');

        if (! $conditionConfig && $serialized) {
            parse_str((string) $serialized, $conditionConfig);
            $conditionConfig = $conditionConfig['condition'] ?? null;
        }

        /** @var ElementConditionInterface $condition */
        $condition = $conditionConfig
            ? $this->conditions->createCondition($conditionConfig)
            : $elementType::createCondition();

        if (! empty($fieldLayouts)) {
            $condition->setFieldLayouts($fieldLayouts);
        }

        $condition->mainTag = 'div';
        $condition->id = (string) $id;
        $condition->addRuleLabel = t('Add a filter');

        $this->elementIndexes->populateFilterHudQueryParams($condition, $source, $sourceKey, $currentCondition);
        $currentElementIndex->activate();

        return new JsonResponse([
            'hudHtml' => $condition->getBuilderHtml(),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function elementTableHtml(ElementIndexRequest $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $elementType = $request->elementType();
        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $request->input('source'), $request->context());
        $elementQuery = $this->elementIndexes->buildQueryState(
            elementType: $elementType,
            source: $source,
            condition: $request->condition(),
            baseCriteria: $request->baseCriteria(),
            criteria: $request->criteria(),
            filterConditionConfig: $request->filterConditionConfig(),
            collapsedElementIds: $request->collapsedElementIds(),
        )['query'];

        abort_if(! $sourceKey, 400, 'Request missing required body param');

        /** @var ElementInterface|null $element */
        $element = (clone $elementQuery)
            ->draftOf($request->integer('id'))
            ->draftCreator($request->craftUser()?->asElement())
            ->provisionalDrafts()
            ->status(null)
            ->one();

        if (! $element) {
            /** @var ElementInterface|null $element */
            $element = (clone $elementQuery)
                ->id($request->integer('id'))
                ->status(null)
                ->one();
        }

        abort_if(! $element, 400, 'Invalid element ID: '.$request->integer('id'));

        $attributes = $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            customAttributes: $request->viewState()['tableColumns'] ?? null,
            fieldLayouts: $request->fieldLayouts(),
        );

        $attributeHtml = [];

        foreach ($attributes as [$attribute]) {
            $attributeHtml[$attribute] = $element->getAttributeHtml($attribute);
        }

        return new JsonResponse([
            'attributeHtml' => $attributeHtml,
        ]);
    }
}
