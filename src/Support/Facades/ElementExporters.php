<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface[] availableExporters(string $elementType, string $sourceKey)
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface createExporter(\CraftCms\Cms\Element\Contracts\ElementExporterInterface|string|array $exporter, string $elementType)
 * @method static array serializeExporters(iterable $exporters)
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface|null resolveExporter(iterable $exporters, string $exporterClass)
 * @method static \Symfony\Component\HttpFoundation\Response export(\CraftCms\Cms\Element\Contracts\ElementExporterInterface $exporter, \CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query, string $format = 'csv')
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
