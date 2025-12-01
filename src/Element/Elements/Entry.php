<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Elements;

use CraftCms\Cms\Database\Queries\EntryQuery;

/**
 * @TODO: Port Entry element fully
 */
final class Entry extends \craft\elements\Entry
{
    #[\Override]
    public static function find(): EntryQuery
    {
        return new EntryQuery;
    }
}
