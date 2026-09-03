<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
interface ImportableInterface
{
    /**
     * Returns whether the component can be imported into directly.
     */
    public static function isImportable(): bool;

    /**
     * Returns the class name of the default transformer for the component.
     */
    public static function getDefaultTransformer(): ?string;

    /**
     * Prepares a new element instance for import.
     */
    public function prepareNewElementForImport(BaseImporter $importer, array &$data): self;

    /**
     * Prepare query that searches for the root element we're importing into
     */
    public function prepareRootElementImportQuery(ElementQuery $query): ElementQuery;

    /**
     * Sets element's importable attributes.
     */
    public function setAttributesForImport(array $attributes): void;

    /**
     * Marks the component as currently being imported.
     * That way we don't need a logged-in user for some data to import
     * (e.g. the element's authors)
     */
    public function markAsImporting(): void;
}
