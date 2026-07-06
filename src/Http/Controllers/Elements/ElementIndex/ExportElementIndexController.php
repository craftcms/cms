<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\ElementIndex;

use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Validation\Rules\ElementTypeRule;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use Symfony\Component\HttpFoundation\Response;

class ExportElementIndexController
{
    private ElementIndexRequest $request;

    use InteractsWithElementIndexes;

    public function __construct(
        private ElementExporters $elementExporters,
    ) {}

    public function __invoke(ElementIndexRequest $request): Response
    {
        $this->request = $request;

        $validated = $this->request->validate([
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
        $context = $this->request->context();

        [$sourceKey, $source] = $this->resolveSource($elementType, $this->request->input('source'), $context);
        abort_if(! isset($sourceKey), 400, 'Request missing required body param');
        abort_if(! $this->request->isAdministrative(), 400, 'Request missing index context');

        $exporters = $this->availableExporters($elementType, $sourceKey);
        $exporter = $this->elementExporters->resolveExporter(
            $exporters,
            $this->request->input('type', Raw::class),
        );

        abort_if($exporter === null, 400, 'Element exporter is not supported by the element type');

        return $this->elementExporters->export(
            exporter: $exporter,
            query: $this->buildElementQueryState(
                elementType: $elementType,
                source: $source,
                condition: $this->request->condition()
            )['query'],
            format: $this->request->input('format', 'csv'),
        );
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return ElementExporterInterface[]
     */
    private function availableExporters(string $elementType, string $sourceKey): array
    {
        if ($this->request->isMobileBrowser()) {
            return [];
        }

        return $this->elementExporters->availableExporters($elementType, $sourceKey);
    }
}
