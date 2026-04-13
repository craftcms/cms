<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Exceptions\InvalidTypeException;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\InteractsWithElementIndexes;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class ExportElementIndexController
{
    use InteractsWithElementIndexes;

    public function __construct(
        private Request $request,
        private ElementExporters $elementExporters,
    ) {}

    public function __invoke(): Response
    {
        $validated = $this->request->validate([
            'elementType' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! is_subclass_of($value, ElementInterface::class)) {
                        $fail(new InvalidTypeException((string) $value, ElementInterface::class)->getMessage());
                    }
                },
            ],
            'type' => ['sometimes', 'string'],
            'format' => ['sometimes', 'string'],
        ]);

        /** @var class-string<ElementInterface> $elementType */
        $elementType = $validated['elementType'];
        $context = $this->request->input('context', ElementSources::CONTEXT_INDEX);

        [$sourceKey, $source] = $this->source($elementType, $this->request->input('source'), $context);
        abort_if(! isset($sourceKey), 400, 'Request missing required body param');
        abort_if(! $this->isAdministrative($context), 400, 'Request missing index context');

        $exporters = $this->availableExporters($elementType, $sourceKey);
        $exporter = $this->elementExporters->resolveExporter(
            $exporters,
            $this->request->input('type', Raw::class),
        );

        abort_if($exporter === null, 400, 'Element exporter is not supported by the element type');

        return $this->elementExporters->export(
            exporter: $exporter,
            query: $this->elementQuery($elementType, $source, $this->condition()),
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
