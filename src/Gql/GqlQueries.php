<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Gql\Queries\Address;
use CraftCms\Cms\Gql\Queries\Asset;
use CraftCms\Cms\Gql\Queries\Entry;
use CraftCms\Cms\Gql\Queries\Ping;
use CraftCms\Cms\Gql\Queries\Query;
use CraftCms\Cms\Gql\Queries\User;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers query classes available to GraphQL schemas.
 *
 * ```php
 * public function boot(GqlQueries $queries): void
 * {
 *     $queries->register(MyQuery::class);
 * }
 * ```
 *
 * @extends TypeRegistry<Query>
 */
#[Singleton]
class GqlQueries extends TypeRegistry
{
    protected const string CONTRACT = Query::class;

    protected const array DEFAULT_TYPES = [
        Address::class,
        Ping::class,
        Entry::class,
        Asset::class,
        User::class,
    ];
}
