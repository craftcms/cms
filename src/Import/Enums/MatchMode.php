<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Enums;

/**
 * What an element import does with a row, depending on whether its match criteria find an
 * existing element.
 *
 * @since 6.0.0
 */
enum MatchMode: string
{
    /** Rows that match update the element they match; the rest create new ones. */
    case CreateAndUpdate = 'createAndUpdate';

    /** Only rows that match nothing are imported, as new elements. */
    case CreateOnly = 'createOnly';

    /** Only rows that match an existing element are imported, updating it. */
    case UpdateOnly = 'updateOnly';

    /** Whether a row is imported, given whether it matched an existing element. */
    public function imports(bool $matched): bool
    {
        return match ($this) {
            self::CreateAndUpdate => true,
            self::CreateOnly => ! $matched,
            self::UpdateOnly => $matched,
        };
    }
}
