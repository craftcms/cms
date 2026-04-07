<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Facade;
use Override;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static array availableExporters(string $elementType, string $sourceKey)
 * @method static ElementExporterInterface createExporter(mixed $exporter, string $elementType)
 * @method static array serializeExporters(iterable $exporters)
 * @method static ElementExporterInterface|null resolveExporter(iterable $exporters, string $exporterClass)
 * @method static Response export(ElementExporterInterface $exporter, ElementQueryInterface $query, string $format = 'csv')
 *
 * @see \CraftCms\Cms\Element\ElementExporters
 */
class ElementExporters extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Element\ElementExporters::class;
    }
}
