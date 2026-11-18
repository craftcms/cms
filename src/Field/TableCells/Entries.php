<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Field\Entries as EntriesField;

/**
 * Entries cells relate entries.
 *
 * @since 6.0.0
 */
class Entries extends BaseRelationCell
{
    public static function fieldType(): string
    {
        return EntriesField::class;
    }
}
