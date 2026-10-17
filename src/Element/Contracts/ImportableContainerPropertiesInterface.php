<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Import\Data\CompoundMappingColumn;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * Implemented by element types with importable container properties (`#[Importable(..., isContainer: true)]`),
 * which can’t be set like ordinary attributes, e.g. a user’s addresses.
 *
 * @since 6.0.0
 */
interface ImportableContainerPropertiesInterface
{
    /**
     * Returns the mapping columns for an importable container property, or null if it isn’t one.
     *
     * @return list<MappingColumn|CompoundMappingColumn>|null
     */
    public static function getDestinationColsForProperty(BaseImporter $importer, string $property): ?array;

    /**
     * Imports the incoming data for a container property, normalizing it and saving whatever it needs to.
     *
     * @param  array<string, mixed>  $attribute  The property’s importable descriptor.
     * @param  array<string, mixed>  $item  The incoming item data.
     */
    public function importIntoContainerAttribute(array $attribute, array $item, BaseImporter $importer): void;
}
