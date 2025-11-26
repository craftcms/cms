<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Elements;

use CraftCms\Cms\Database\Queries\ContentBlockQuery;

final class ContentBlock extends \craft\elements\ContentBlock
{
    #[\Override]
    public static function find(): ContentBlockQuery
    {
        return new ContentBlockQuery;
    }
}
