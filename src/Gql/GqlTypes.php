<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Interfaces\Elements\Address;
use CraftCms\Cms\Gql\Interfaces\Elements\Asset;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry;
use CraftCms\Cms\Gql\Interfaces\Elements\User;
use CraftCms\Cms\Gql\Types\DateTime;
use CraftCms\Cms\Gql\Types\Number;
use CraftCms\Cms\Gql\Types\QueryArgument;
use Illuminate\Container\Attributes\Singleton;

/**
 * Registers singular type classes available to GraphQL schemas.
 *
 * ```php
 * public function boot(GqlTypes $types): void
 * {
 *     $types->register(MyType::class);
 * }
 * ```
 *
 * @extends TypeRegistry<SingularTypeInterface>
 */
#[Singleton]
class GqlTypes extends TypeRegistry
{
    protected const string CONTRACT = SingularTypeInterface::class;

    protected const array DEFAULT_TYPES = [
        DateTime::class,
        Number::class,
        QueryArgument::class,
        Address::class,
        Element::class,
        Entry::class,
        Asset::class,
        User::class,
    ];

    /** @param class-string<SingularTypeInterface> $type */
    #[\Override]
    protected function identity(string $type): string
    {
        return $type::getName();
    }
}
