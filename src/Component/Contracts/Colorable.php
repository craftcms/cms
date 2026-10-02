<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Shared\Enums\Color;

/**
 * Colorable defines the common interface to be implemented by components that
 * can have colors within the control panel.
 */
interface Colorable
{
    /**
     * Returns the HTML for the component’s thumbnail, if it has one.
     */
    public function getColor(): ?Color;
}
