<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Condition\Conditions;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\Resources\ElementIndexResource;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\t;

readonly class ElementIndexController
{
    use InteractsWithElementIndexes;

    public function __construct(
        private Conditions $conditions,
        private ElementIndexRequest $request,
        private ElementSources $elementSources,
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

    public function countElements(): JsonResponse
    {
        $elementType = $this->request->elementType();
        [$sourceKey, $source] = $this->resolveSource($elementType, $this->request->input('source'), $this->request->context());
        $elementQueryState = $this->buildElementQueryState(
            elementType: $elementType,
            source: $source,
            condition: $this->request->condition(),
        );

        $total = $elementType::indexElementCount($elementQueryState['query'], $sourceKey);
        $unfilteredTotal = $elementQueryState['unfilteredQuery']
            ? $elementType::indexElementCount($elementQueryState['unfilteredQuery'], $sourceKey)
            : $total;

        return new JsonResponse([
            'resultSet' => $this->request->input('resultSet'),
            'total' => $total,
            'unfilteredTotal' => $unfilteredTotal,
        ]);
    }

    public function filterHud(CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $elementType = $this->request->elementType();
        $context = $this->request->context();
        [$sourceKey, $source] = $this->resolveSource($elementType, $this->request->input('source'), $context);
        $fieldLayouts = $this->resolveFieldLayouts();
        $currentCondition = $this->resolveElementIndexCondition();
        $id = $this->request->input('id');

        abort_if($id === null || $id === '', 400, 'Request missing required body param');

        $conditionConfig = $this->request->input('conditionConfig');
        $serialized = $this->request->input('serialized');

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

        $this->populateFilterHudQueryParams($condition, $source, $sourceKey, $currentCondition);
        $currentElementIndex->activate();

        return new JsonResponse([
            'hudHtml' => $condition->getBuilderHtml(),
            'headHtml' => HtmlStack::headHtml(),
            'bodyHtml' => HtmlStack::bodyHtml(),
        ]);
    }

    public function elementTableHtml(): JsonResponse
    {
        $this->request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $elementType = $this->request->elementType();
        [$sourceKey, $source] = $this->resolveSource($elementType, $this->request->input('source'), $this->request->context());
        $elementQuery = $this->buildElementQueryState(
            elementType: $elementType,
            source: $source,
            condition: $this->request->condition(),
        )['query'];

        abort_if(! $sourceKey, 400, 'Request missing required body param');

        /** @var ElementInterface|null $element */
        $element = (clone $elementQuery)
            ->draftOf($this->request->integer('id'))
            ->draftCreator($this->request->craftUser()?->asElement())
            ->provisionalDrafts()
            ->status(null)
            ->one();

        if (! $element) {
            /** @var ElementInterface|null $element */
            $element = (clone $elementQuery)
                ->id($this->request->integer('id'))
                ->status(null)
                ->one();
        }

        abort_if(! $element, 400, 'Invalid element ID: '.$this->request->integer('id'));

        $attributes = $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            customAttributes: $this->resolveViewState()['tableColumns'] ?? null,
            fieldLayouts: $this->resolveFieldLayouts(),
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
