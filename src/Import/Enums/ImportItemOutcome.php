<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * What happened to an item in an import run.
 *
 * @since 6.0.0
 */
enum ImportItemOutcome: string
{
    case Created = 'created';

    case Updated = 'updated';

    /** It matched something that already had its values, so nothing was saved. */
    case Unchanged = 'unchanged';

    /** The plan left it out, by its match mode or an event listener. */
    case Skipped = 'skipped';

    case Failed = 'failed';
}
