<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Element\CurrentElementIndex;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\Requests\ElementRequest;
use Illuminate\Http\JsonResponse;

use function CraftCms\Cms\template;

readonly class ElementIndexSourcesController
{
    use InteractsWithElementIndexes;

    public function __construct(
        private ElementRequest $request,
        private ElementSources $elementSources,
    ) {}

    public function sourcePath(CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $this->request->validate([
            'stepKey' => ['required', 'string'],
        ]);

        $elementType = $this->request->elementType();
        $sourceKey = $this->request->input('source', '');
        $stepKey = $this->request->input('stepKey');

        $currentElementIndex->activate();

        return new JsonResponse([
            'sourcePath' => $elementType::sourcePath(
                sourceKey: $sourceKey,
                stepKey: $stepKey,
                context: $this->request->context(),
            ),
        ]);
    }

    public function sourceAttributeInfo(CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $elementType = $this->request->elementType();
        $context = $this->request->context();
        [$sourceKey] = $this->resolveSource($elementType, $this->request->input('source'), $context);
        $fieldLayouts = $this->resolveFieldLayouts();

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

    public function getSourceTreeHtml(CurrentElementIndex $currentElementIndex): JsonResponse
    {
        $currentElementIndex->activate();

        $sources = $this->elementSources->getSources(
            elementType: $elementType = $this->request->elementType(),
            context: $this->request->context(),
        )->all();

        return new JsonResponse([
            'html' => template('_elements/sources', [
                'elementType' => $elementType,
                'sources' => $sources,
            ]),
        ]);
    }
}
