<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * What importing an item would do, as an import plan's preview shows it.
 *
 * @since 6.0.0
 */
enum ItemPreviewResult: string
{
    /** It would create an element. */
    case New = 'new';

    /** It would update the element it matches. */
    case Update = 'update';

    /** It matches an element that already has its values. */
    case Unchanged = 'unchanged';

    /** The plan leaves it out, by its match mode or an event listener. */
    case Skip = 'skip';

    /** It wouldn't import, because the element wouldn't validate or the item couldn't be read. */
    case Problem = 'problem';
}
