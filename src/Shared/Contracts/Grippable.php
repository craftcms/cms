<?php

namespace CraftCms\Cms\Shared\Contracts;

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
