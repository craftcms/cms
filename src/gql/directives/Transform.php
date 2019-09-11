<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use Craft;
use craft\gql\arguments\Transform as TransformArguments;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

/**
 * Class Transform
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Transform extends Directive
{
    public function __construct(array $config)
    {
        $args = &$config['args'];

        foreach ($args as &$argument) {
            $argument = new FieldArgument($argument);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => TransformArguments::getArguments(),
            'description' => 'This directive is used to return a URL for an [asset tranform](https://docs.craftcms.com/v3/image-transforms.html). It accepts the same arguments you would use for a transform in Craft and adds the `immediately` argument.'
        ]));

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function name(): string
    {
        return 'transform';
    }

    /**
     * @inheritdoc
     */
    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        if ($resolveInfo->fieldName !== 'url') {
            return $value;
        }

        $generateNow = $arguments['immediately'] ?? Craft::$app->getConfig()->general->generateTransformsBeforePageLoad;
        unset($arguments['immediately']);

        if (!empty($arguments['handle'])) {
            $transform = $arguments['handle'];
        } else {
            $transform = $arguments;
        }

        return Craft::$app->getAssets()->getAssetUrl($source, $transform, $generateNow);
    }
}
