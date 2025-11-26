<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Elements;

use CraftCms\Cms\Database\Queries\UserQuery;

final class User extends \craft\elements\User
{
    #[\Override]
    public static function find(): UserQuery
    {
        return new UserQuery;
    }
}
