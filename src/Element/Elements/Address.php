<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Elements;

use CraftCms\Cms\Database\Queries\AddressQuery;

final class Address extends \craft\elements\Address
{
    #[\Override]
    public static function find(): AddressQuery
    {
        return new AddressQuery;
    }
}
