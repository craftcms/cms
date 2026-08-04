<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;

function entryQuery(array $config = []): EntryQuery
{
    return new EntryQuery($config);
}

function assetQuery(array $config = []): AssetQuery
{
    return new AssetQuery($config);
}

function userQuery(array $config = []): UserQuery
{
    return new UserQuery($config);
}
