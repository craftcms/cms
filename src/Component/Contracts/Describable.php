<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

/**
 * Describable defines the common interface to be implemented by components that
 * have description within their chips.
 */
interface Describable
{
    /**
     * Returns the component’s description.
     */
    public function getDescription(): ?string;
}
