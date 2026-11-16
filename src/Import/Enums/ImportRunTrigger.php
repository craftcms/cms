<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * How an import run was started.
 *
 * @since 6.0.0
 */
enum ImportRunTrigger: string
{
    case ControlPanel = 'cp';

    case Console = 'cli';
}
