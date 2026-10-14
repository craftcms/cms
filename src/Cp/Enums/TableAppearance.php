<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

/**
 * How a table meets its container. Mirrors `TableAppearance` in
 * `resources/js/common/types`.
 *
 * @since 6.0.0
 */
enum TableAppearance: string
{
    /** Sits inside the container’s padding. */
    case Plain = 'plain';

    /** Spans the container’s inline padding, padding each row’s outer cells to match. */
    case Flush = 'flush';

    /** Sits in a raised pane. */
    case Raised = 'raised';
}
