<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * Where an import plan's latest run, or one of its steps, has got to.
 *
 * @since 6.0.0
 */
enum ImportRunStatus: string
{
    /** Queued, and not started yet. */
    case Pending = 'pending';

    case Running = 'running';

    /** Finished with every item imported. */
    case Completed = 'completed';

    /** Finished with errors, or stopped before it finished. */
    case Failed = 'failed';
}
