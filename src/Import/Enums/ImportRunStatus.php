<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * Where an import run, or one of its step runs, stands.
 *
 * @since 6.0.0
 */
enum ImportRunStatus: string
{
    /** Queued, and not started yet. */
    case Pending = 'pending';

    case Running = 'running';

    /** Finished with no failed items. */
    case Successful = 'successful';

    /** Finished with at least one failed item, or didn't finish. */
    case Failed = 'failed';

    public function isFinished(): bool
    {
        return $this === self::Successful || $this === self::Failed;
    }
}
