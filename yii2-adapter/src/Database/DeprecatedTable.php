<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Database;

/**
 * This class provides constants for defining Craft’s deprecated database table names.
 */
readonly class DeprecatedTable
{
    public const string CATEGORIES = 'categories';

    public const string CATEGORYGROUPS = 'categorygroups';

    public const string CATEGORYGROUPS_SITES = 'categorygroups_sites';

    public const string TAGGROUPS = 'taggroups';

    public const string TAGS = 'tags';

    public const string GLOBALSETS = 'globalsets';
}
