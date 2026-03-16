<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Directives;

use CraftCms\Cms\Gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\helpers\Markdown as MarkdownHelper;

class Markdown extends Directive
{
    public const DEFAULT_FLAVOR = null;

    public const DEFAULT_INLINE_ONLY = false;

    public static function create(): GqlDirective
    {
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'flavor',
                    'type' => Type::string(),
                    'defaultValue' => self::DEFAULT_FLAVOR,
                    'description' => 'The “flavor” of Markdown the input should be interpreted with. Accepts the same arguments as yii\\helpers\\Markdown::process().',
                ]),
                new FieldArgument([
                    'name' => 'inlineOnly',
                    'type' => Type::boolean(),
                    'defaultValue' => self::DEFAULT_INLINE_ONLY,
                    'description' => 'Whether to only parse inline elements, omitting any `<p>` tags.',
                ]),
            ],
            'description' => 'Parses the passed field value as Markdown.',
        ]));
    }

    public static function name(): string
    {
        return 'markdown';
    }

    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        $inlineOnly = $arguments['inlineOnly'] ?? self::DEFAULT_INLINE_ONLY;

        if ($inlineOnly) {
            return MarkdownHelper::processParagraph((string) $value, $arguments['flavor'] ?? self::DEFAULT_FLAVOR);
        }

        return MarkdownHelper::process((string) $value, $arguments['flavor'] ?? self::DEFAULT_FLAVOR);
    }
}
