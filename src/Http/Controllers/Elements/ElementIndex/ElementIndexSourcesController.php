<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\template;

class ElementIndexSourcesController
{
    public function __construct(
        private readonly ElementIndexes $elementIndexes,
        private readonly ElementSources $elementSources,
    ) {}

    public function sourcePath(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $request->validate([
            'stepKey' => ['required', 'string'],
        ]);

        $elementType = $request->elementType();
        $sourceKey = $request->input('source', '');
        $stepKey = $request->input('stepKey');

        $currentElementIndex->activate();

        return new JsonResponse([
            'sourcePath' => $elementType::sourcePath(
                sourceKey: $sourceKey,
                stepKey: $stepKey,
                context: $request->context(),
            ),
        ]);
    }

    public function sourceAttributeInfo(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $elementType = $request->elementType();
        $context = $request->context();
        [$sourceKey] = $this->elementIndexes->resolveSource($elementType, $request->input('source'), $context);
        $fieldLayouts = $request->fieldLayouts();

        $currentElementIndex->activate();

        if (! $sourceKey) {
            return new JsonResponse([
                'sortOptions' => [],
                'tableColumns' => [],
                'defaultTableColumns' => [],
            ]);
        }

        $sortOptions = $this->elementSources->getSourceSortOptions($elementType, $sourceKey)
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'attr' => $option['attribute'] ?? $option['orderBy'],
                'defaultDir' => $option['defaultDir'] ?? 'asc',
            ])
            ->values()
            ->all();

        $tableColumns = $this->elementSources->getSourceTableAttributes($elementType, $sourceKey)
            ->map(fn (array $attribute, string $key) => [
                ...$attribute,
                'attr' => $key,
            ])
            ->values()
            ->all();

        $defaultTableColumns = $this->elementSources->getTableAttributes(
            elementType: $elementType,
            sourceKey: $sourceKey,
            fieldLayouts: $fieldLayouts,
        )
            ->map(fn (array $attribute) => $attribute[0])
            ->filter(fn (string $attribute) => $attribute !== 'title')
            ->values()
            ->all();

        return new JsonResponse(compact(
            'sortOptions',
            'tableColumns',
            'defaultTableColumns',
        ));
    }

    public function getSourceTreeHtml(ElementIndexRequest $request, CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $currentElementIndex->activate();

        $sources = $this->elementSources->getSources(
            elementType: $elementType = $request->elementType(),
            context: $request->context(),
        )->all();

        return new JsonResponse([
            'html' => template('_elements/sources', [
                'elementType' => $elementType,
                'sources' => $sources,
            ]),
        ]);
    }
}
