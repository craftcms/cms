<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class StripTags extends Directive
{
    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'description' => 'Strips HTML tags from the field value.',
            'args' => [
                [
                    'name' => 'allowed',
                    'type' => Type::listOf(Type::string()),
                    'defaultValue' => [],
                    'description' => 'List of allowed tag names.',
                ],
            ],
        ]));
    }

    public static function name(): string
    {
        return 'stripTags';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        return strip_tags((string) $value, $arguments['allowed'] ?? null);
    }
}
