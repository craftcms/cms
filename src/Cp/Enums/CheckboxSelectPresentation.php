<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\Cp\Components\CheckboxSelect;

/**
 * How a {@see CheckboxSelect} lists its options.
 *
 * @since 6.0.0
 */
enum CheckboxSelectPresentation: string
{
    /** Every option, with its checkbox. */
    case Checkboxes = 'checkboxes';

    /**
     * Only the checked options, each with a Remove button, with the rest in an
     * “Add item” menu.
     */
    case AddMenu = 'add-menu';
}
