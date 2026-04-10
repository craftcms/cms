<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

/**
 * ImportableElementContainerFieldInterface defines the common interface to be implemented by field classes
 * that contain nested elements and wish to support importing content via the import mechanism.
 */
interface ImportableElementContainerFieldInterface extends FieldInterface
{
    /**
     * Copies the field’s value from one site to another.
     */
    public function normalizeValueForImport(mixed $value): array;
}
