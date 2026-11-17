<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\TableCells;

use CraftCms\Cms\Field\Users as UsersField;

/**
 * Users cells relate users.
 *
 * @since 6.0.0
 */
class Users extends BaseRelationCell
{
    public static function fieldType(): string
    {
        return UsersField::class;
    }
}
