<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Import\Importers\BaseImporter;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
interface Importable
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
    public function prepareNewElementForImport(BaseImporter $config, array &$data): self;
}
