<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * StripTags GraphQL Directive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.4
 */
class StripTags extends Directive
{
    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'description' => 'Strips HTML tags from the field value.',
            'args' => [
                new FieldArgument([
                    'name' => 'allowed',
                    'type' => Type::listOf(Type::string()),
                    'defaultValue' => [],
                    'description' => 'List of allowed tag names.',
                ]),
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'stripTags';
    }

    /**
     * @inheritdoc
     */
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        return strip_tags((string)$value, $arguments['allowed'] ?? null);
    }
}
