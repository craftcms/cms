<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Gql\Mutations\Asset;
use CraftCms\Cms\Gql\Mutations\Entry;
use CraftCms\Cms\Gql\Mutations\Mutation;
use CraftCms\Cms\Gql\Mutations\Ping;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers mutation classes available to GraphQL schemas.
 *
 * ```php
 * public function boot(GqlMutations $mutations): void
 * {
 *     $mutations->register(MyMutation::class);
 * }
 * ```
 *
 * @extends TypeRegistry<Mutation>
 */
#[Singleton]
class GqlMutations extends TypeRegistry
{
    protected const string CONTRACT = Mutation::class;

    protected const array DEFAULT_TYPES = [
        Ping::class,
        Entry::class,
        Asset::class,
    ];
}
