<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use Symfony\Component\HttpFoundation\Response;

class ExportElementIndexController
{
    public function __construct(
        private readonly ElementExporters $elementExporters,
        private readonly ElementIndexes $elementIndexes,
    ) {}

    public function __invoke(ElementIndexRequest $request): Response
    {
        $validated = $request->validate([
            'elementType' => [
                'required',
                'string',
                new ElementTypeRule,
            ],
            'type' => ['sometimes', 'string'],
            'format' => ['sometimes', 'string'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $validated['elementType'];
        $context = $request->context();

        [$sourceKey, $source] = $this->elementIndexes->resolveSource($elementType, $request->input('source'), $context);
        abort_if(! isset($sourceKey), 400, 'Request missing required body param');
        abort_if(! $request->isAdministrative(), 400, 'Request missing index context');

        $exporters = $request->isMobileBrowser()
            ? []
            : $this->elementExporters->availableExporters($elementType, $sourceKey);
        $exporter = $this->elementExporters->resolveExporter(
            $exporters,
            $request->input('type', Raw::class),
        );

        abort_if($exporter === null, 400, 'Element exporter is not supported by the element type');

        return $this->elementExporters->export(
            exporter: $exporter,
            query: $this->elementIndexes->buildQueryState(
                elementType: $elementType,
                source: $source,
                condition: $request->condition(),
                baseCriteria: $request->baseCriteria(),
                criteria: $request->criteria(),
                filterConditionConfig: $request->filterConditionConfig(),
                collapsedElementIds: $request->collapsedElementIds(),
            )['query'],
            format: $request->input('format', 'csv'),
        );
    }
}
