<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Http\EmbeddedNestedElementScope;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Typecast;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
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
        $embedded = $context === ElementSources::CONTEXT_EMBEDDED_INDEX;
        if ($embedded) {
            $nestedElementScope = new EmbeddedNestedElementScope($request);
            $nestedSource = $nestedElementScope->indexSource($elementType);
            $baseCriteria = $nestedSource->criteria;
            $criteria = $nestedElementScope->filterCriteria($request->criteria());
            [$sourceKey, $source] = [$nestedSource::NESTED_KEY, $nestedSource->source];
        } else {
            $baseCriteria = $request->baseCriteria();
            $criteria = $request->criteria();
            [$sourceKey, $source] = $this->elementIndexes->resolveSource(
                $elementType,
                $request->input('source'),
                $context,
            );
        }
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

        $query = $this->elementIndexes->buildQueryState(
            elementType: $elementType,
            source: $source,
            condition: $request->condition(),
            baseCriteria: $baseCriteria,
            criteria: $criteria,
            filterConditionConfig: $request->filterConditionConfig(),
            collapsedElementIds: $request->collapsedElementIds(),
        )['query'];

        $sort = array_values(array_filter(
            $request->array('sort'),
            fn (mixed $item): bool => is_array($item) && is_string($item['field'] ?? null) && is_string($item['direction'] ?? null),
        ));

        if ($sort !== []) {
            $this->elementIndexes->applySort(
                elementType: $elementType,
                elementQuery: $query,
                sourceKey: $sourceKey,
                sort: $sort,
                reset: true,
                source: $source,
            );
        }

        if ($embedded) {
            Typecast::configure($query, ElementHelper::cleanseQueryCriteria($baseCriteria));
        }

        return $this->elementExporters->export(
            exporter: $exporter,
            query: $query,
            format: $request->input('format', 'csv'),
        );
    }
}
