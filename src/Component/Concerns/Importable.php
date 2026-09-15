<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

/**
 * Importable defines the common interface to be implemented by components that
 * can use the import feature.
 */
trait Importable
{
    public private(set) bool $importing = false;

    public function markAsImporting(): void
    {
        $this->importing = true;
    }
}
