<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\ColorData;
use GraphQL\Type\Definition\Type;

/**
 * Class ColorDataType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class ColorDataType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    /**
     * Returns the generator name.
     */
    public static function getName(): string
    {
        return 'ColorData';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();
        return GqlEntityRegistry::getOrCreate($typeName, fn() => new ColorData([
            'name' => $typeName,
            'fields' => fn() => Craft::$app->getGql()->prepareFieldDefinitions([
                'hex' => [
                    'name' => 'hex',
                    'type' => Type::string(),
                    'args' => [
                        'withAlpha' => [
                            'name' => 'withAlpha',
                            'type' => Type::boolean(),
                            'defaultValue' => true,
                            'description' => 'Whether to include the alpha channel, if the color isn’t fully opaque.',
                        ],
                    ],
                ],
                'label' => Type::string(),
                'red' => Type::int(),
                'green' => Type::int(),
                'blue' => Type::int(),
                'alpha' => Type::float(),
                'hue' => Type::int(),
                'saturation' => Type::int(),
                'lightness' => Type::int(),
                'luma' => Type::float(),
                'rgb' => Type::string(),
                'rgba' => Type::string(),
                'rgbChannels' => Type::string(),
                'hsl' => Type::string(),
                'hsla' => Type::string(),
                'oklch' => Type::string(),
                'contrastColor' => Type::string(),
                'contrastRatio' => [
                    'name' => 'contrastRatio',
                    'type' => Type::float(),
                    'args' => [
                        'with' => [
                            'name' => 'with',
                            'type' => Type::nonNull(Type::string()),
                            'description' => 'The hex value of the color to compare with.',
                        ],
                    ],
                ],
            ], $typeName),
        ]));
    }
}
