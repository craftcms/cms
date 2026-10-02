<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Grippable defines the common interface to be implemented by components that
 * can be identified by a handle.
 */
interface Grippable
{
    /**
     * Returns the handle of the component.
     */
    public function getHandle(): ?string;
}
