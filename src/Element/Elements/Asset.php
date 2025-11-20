<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Elements;

use CraftCms\Cms\Database\Queries\AssetQuery;
use Override;

/**
 * @TODO: Port Asset element fully
 */
final class Asset extends \craft\elements\Asset
{
    #[Override]
    public static function find(): AssetQuery
    {
        return new AssetQuery;
    }
}
