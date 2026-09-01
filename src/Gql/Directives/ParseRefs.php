<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\Elements;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;

class ParseRefs extends Directive
{
    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'description' => 'Parses the element references on the field.',
            'args' => [],
        ]));
    }

    public static function name(): string
    {
        return 'parseRefs';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        return Elements::parseRefs((string) $value);
    }
}
