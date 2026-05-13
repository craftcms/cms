<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

interface DefaultableFieldInterface extends FieldInterface
{
    /**
     * Returns the default value that should be set on existing elements.
     */
    public function getDefaultValue(): mixed;
}
