<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
interface Importable
{
    /**
     * Returns the class name of the default transformer for the component.
     */
    public static function getDefaultTransformer(): ?string;
}
