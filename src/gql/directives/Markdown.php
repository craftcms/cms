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
use yii\helpers\Markdown as MarkdownHelper;

/**
 * Markdown GraphQL Directive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.1
 */
class Markdown extends Directive
{
    const DEFAULT_FLAVOR = null;

    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => [
                new FieldArgument([
                    'name' => 'flavor',
                    'type' => Type::string(),
                    'defaultValue' => self::DEFAULT_FLAVOR,
                    'description' => 'The “flavor” of Markdown the input should be interpreted with. Accepts the same arguments as yii\\helpers\\Markdown::process().'
                ]),
            ],
            'description' => 'Parses the passed field value as Markdown.'
        ]));

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'markdown';
    }

    /**
     * @inheritdoc
     */
    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        return MarkdownHelper::process((string)$value, $arguments['flavor'] ?? self::DEFAULT_FLAVOR);
    }
}
