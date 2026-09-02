<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Transformers\ElementTransformer;
use Override;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
trait Importable
{
    public private(set) bool $importing = false;

    #[Override]
    public static function isImportable(): bool
    {
        return true;
    }

    #[Override]
    public static function getDefaultTransformer(): ?string
    {
        return ElementTransformer::class;
    }

    #[Override]
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

    #[Override]
    public function prepareRootElementImportQuery(ElementQuery $query): ElementQuery
    {
        // by default, we don't need to adjust the element query
        return $query;
    }

    #[Override]
    public function setAttributesForImport(array $attributes): void
    {
        // the ID and UID can only be used to match on, we cannot have them be set via the import
        unset($attributes['id'], $attributes['uid']);

        // by default, simply set the attributes
        $this->setAttributesFromRequest($attributes);
    }
}
