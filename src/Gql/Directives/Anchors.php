<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\Anchors as AnchorsFacade;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class Anchors extends Directive
{
    public const array DEFAULT_TAGS = ['h1', 'h2', 'h3'];

    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                [
                    'name' => 'tags',
                    'type' => Type::listOf(Type::string()),
                    'defaultValue' => self::DEFAULT_TAGS,
                    'description' => 'The tags to add anchor links to.',
                ],
                [
                    'name' => 'language',
                    'type' => Type::string(),
                    'defaultValue' => null,
                    'description' => 'The content language, used when converting non-ASCII characters to ASCII.',
                ],
            ],
            'description' => 'Adds anchor links to the headings in the field value.',
        ]));
    }

    public static function name(): string
    {
        return 'anchors';
    }

    /** @param array<string, mixed> $arguments */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        return AnchorsFacade::parseHtml(
            (string) $value,
            $arguments['tags'] ?? self::DEFAULT_TAGS,
            $arguments['language'] ?? null,
        );
    }
}
