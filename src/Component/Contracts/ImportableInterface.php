<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
interface ImportableInterface
{
    /**
     * Marks the component as currently being imported.
     * That way we don't need a logged-in user for some data to import
     * (e.g. the element's authors)
     */
    public function markAsImporting(): void;
}
