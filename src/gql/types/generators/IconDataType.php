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
use craft\gql\types\IconData;
use GraphQL\Type\Definition\Type;

/**
 * Class IconDataType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class IconDataType implements GeneratorInterface, SingleGeneratorInterface
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
        return 'IconData';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();
        return GqlEntityRegistry::getOrCreate($typeName, fn() => new IconData([
            'name' => $typeName,
            'fields' => function() use ($typeName) {
                $fields = [
                    'name' => Type::string(),
                    'styles' => Type::listOf(Type::string()),
                ];

                return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
