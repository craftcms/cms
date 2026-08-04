<?php

declare(strict_types=1);

namespace CraftCms\Cms\Shared\Contracts;

interface Serializable
{
    /**
     * Returns the object’s serialized value.
     */
    public function serialize(): mixed;
}
