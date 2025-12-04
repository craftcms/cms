<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Elements;

use CraftCms\Cms\Database\Queries\AddressQuery;
use Override;

final class Address extends \craft\elements\Address
{
    #[Override]
    public static function find(): AddressQuery
    {
        return new AddressQuery;
    }
}
