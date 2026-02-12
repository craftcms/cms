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
use Illuminate\Support\Collection;

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
        $typeName = static::name();

        return GqlEntityRegistry::getOrCreate(static::name(), fn() => new self([
            'name' => $typeName,
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'args' => TransformArguments::getArguments(),
            'description' => 'Returns a URL for an [asset transform](https://craftcms.com/docs/5.x/development/image-transforms.html). Accepts the same arguments you would use for a transform in Craft.',
        ]));
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
    public static function apply(mixed $source, mixed $value, array $arguments, ResolveInfo $resolveInfo): mixed
    {
        if (empty($arguments)) {
            return $value;
        }

        $transform = Gql::prepareTransformArguments($arguments);

        if ($value instanceof Asset) {
            $value->setTransform($transform);
        } elseif ($value instanceof Collection || is_array($value)) {
            foreach ($value as $asset) {
                // If this somehow ended up being a mix of elements, don't explicitly fail, just set the transform on the asset elements
                if ($asset instanceof Asset) {
                    $asset->setTransform($transform);
                }
            }
        } elseif ($source instanceof Asset) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $allowTransform = match ($source->getMimeType()) {
                'image/gif' => $generalConfig->transformGifs,
                'image/svg+xml' => $generalConfig->transformSvgs,
                default => true,
            };
            if (!$allowTransform) {
                $transform = null;
            }

            switch ($resolveInfo->fieldName) {
                case 'format':
                    return $source->getFormat($transform);
                case 'height':
                    return $source->getHeight($transform);
                case 'mimeType':
                    return $source->getMimeType($transform);
                case 'url':
                    $generateNow = $arguments['immediately'] ?? $generalConfig->generateTransformsBeforePageLoad;
                    return $source->getUrl($transform, $generateNow);
                case 'width':
                    return $source->getWidth($transform);
            }
        }

        return $value;
    }
}
