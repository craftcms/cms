<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * Component sizes shared across the CP component system (the union of the
 * `size` properties in `@craftcms/cp`; not every component accepts every
 * size).
 */
enum Size: string
{
    case Zero = 'zero';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
}
