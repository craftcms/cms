<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Transformers\ElementTransformer;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
trait Importable
{
    public private(set) bool $importing = false;

    /**
     * Returns whether the component can be imported into directly.
     */
    public static function isImportable(): bool
    {
        return true;
    }

    /**
     * Returns the class name of the default transformer for the component.
     */
    public static function getDefaultTransformer(): ?string
    {
        return ElementTransformer::class;
    }

    /**
     * Prepares a new element instance for import.
     */
    public function prepareNewElementForImport(BaseImporter $importer, array &$data): self
    {
        // ensure site is set
        $this->siteId = $importer->site->id;

        // mark as being imported
        // that way we don't need a logged in user for some data to import
        // (e.g. the element's authors)
        $this->importing = true;

        return $this;
    }

    /**
     * Prepare query that searches for the root element we're importing into
     */
    public function prepareRootElementImportQuery(ElementQuery $query): ElementQuery
    {
        // by default, we don't need to adjust the element query
        return $query;
    }

    /**
     * Sets element's importable attributes.
     */
    public function setAttributesForImport(array $attributes): void
    {
        // the ID and UID can only be used to match on, we cannot have them be set via the import
        unset($attributes['id'], $attributes['uid']);

        // by default, simply set the attributes
        $this->setAttributesFromRequest($attributes);
    }
}
