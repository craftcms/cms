<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Elements;

use CraftCms\Cms\Database\Queries\ContentBlockQuery;
use Override;

final class ContentBlock extends \craft\elements\ContentBlock
{
    #[Override]
    public static function find(): ContentBlockQuery
    {
        return new ContentBlockQuery;
    }
}
