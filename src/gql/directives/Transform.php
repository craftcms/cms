<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use Craft;
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
            'args' => [
                new FieldArgument([
                    'name' => 'handle',
                    'type' => Type::string(),
                    'description' => 'The handle of the named transform to use.'
                ]),
                new FieldArgument([
                    'name' => 'width',
                    'type' => Type::int(),
                    'description' => 'Width for the generated transform'
                ]),
                new FieldArgument([
                    'name' => 'height',
                    'type' => Type::int(),
                    'description' => 'Height for the generated transform'
                ]),
                new FieldArgument([
                    'name' => 'mode',
                    'type' => Type::string(),
                    'description' => 'The mode to use for the generated transform.'
                ]),
                new FieldArgument([
                    'name' => 'position',
                    'type' => Type::string(),
                    'description' => 'The position to use when cropping, if no focal point specified.'
                ]),
                new FieldArgument([
                    'name' => 'interlace',
                    'type' => Type::string(),
                    'description' => 'The interlace mode to use for the transform'
                ]),
                new FieldArgument([
                    'name' => 'quality',
                    'type' => Type::int(),
                    'description' => 'The quality of the transform'
                ]),
                new FieldArgument([
                    'name' => 'format',
                    'type' => Type::string(),
                    'description' => 'The format to use for the transform'
                ]),
                new FieldArgument([
                    'name' => 'immediately',
                    'type' => Type::boolean(),
                    'description' => 'Whether the transform should be generated immediately or only when the image is requested used the generated URL'
                ]),
            ],
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
