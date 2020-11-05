<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\directives;

use Craft;
use craft\elements\Asset;
use craft\gql\arguments\Transform as TransformArguments;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;

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
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => TransformArguments::getArguments(),
            'description' => 'This directive is used to return a URL for an [asset transform](https://craftcms.com/docs/3.x/image-transforms.html). It accepts the same arguments you would use for a transform in Craft and adds the `immediately` argument.'
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
        $onAssetElement = $value instanceof Asset;
        $onAssetElementList = is_array($value) && !empty($value);
        $onApplicableAssetField = $source instanceof Asset && in_array($resolveInfo->fieldName, ['height', 'width', 'url']);

        if (!($onAssetElement || $onAssetElementList || $onApplicableAssetField) || empty($arguments)) {
            return $value;
        }

        $generateNow = $arguments['immediately'] ?? Craft::$app->getConfig()->general->generateTransformsBeforePageLoad;
        $transform = Gql::prepareTransformArguments($arguments);

        // If this directive is applied to an entire Asset
        if ($onAssetElement) {
            return $value->setTransform($transform);
        }

        if ($onAssetElementList) {
            foreach ($value as &$asset) {
                // If this somehow ended up being a mix of elements, don't explicitly fail, just set the transform on the asset elements
                if ($asset instanceof Asset) {
                    $asset->setTransform($transform);
                }
            }

            return $value;
        }

        switch ($resolveInfo->fieldName) {
            case 'height':
                return $source->getHeight($transform);
            case 'width':
                return $source->getWidth($transform);
            case 'url':
                return $source->getUrl($transform, $generateNow);
        }

        return $value;
    }
}
