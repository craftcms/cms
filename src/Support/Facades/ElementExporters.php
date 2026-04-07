<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Support\Facades\Facade;
use Override;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface[] availableExporters(string<\craft\base\ElementInterface> $elementType, string $sourceKey)
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface createExporter(\CraftCms\Cms\Element\Contracts\ElementExporterInterface|string<\CraftCms\Cms\Element\Contracts\ElementExporterInterface>|array $exporter, string<\craft\base\ElementInterface> $elementType)
 * @method static array serializeExporters(iterable<\CraftCms\Cms\Element\Contracts\ElementExporterInterface> $exporters)
 * @method static \CraftCms\Cms\Element\Contracts\ElementExporterInterface|null resolveExporter(iterable<\CraftCms\Cms\Element\Contracts\ElementExporterInterface> $exporters, string $exporterClass)
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
