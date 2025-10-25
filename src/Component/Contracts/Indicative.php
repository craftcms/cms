<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Shared\Enums\Color;

/**
 * Indicative defines the common interface to be implemented by components that
 * have indicator icons within their chips.
 */
interface Indicative
{
    /**
     * Returns the component’s indicators.
     *
     * Each indicator should be a nested array with the following keys:
     *
     * - `label` – The indicator label.
     * - `icon` – The indicator icon name. System icons can be found in `src/icons/solid/`.
     * - `iconColor` – The color of the icon.
     *
     * @return array{label:string,icon:string,iconColor?:Color|string}[]
     */
    public function getIndicators(): array;
}
