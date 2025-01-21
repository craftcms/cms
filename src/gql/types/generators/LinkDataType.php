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
use craft\gql\types\LinkData;
use GraphQL\Type\Definition\Type;

/**
 * Class LinkDataType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class LinkDataType implements GeneratorInterface, SingleGeneratorInterface
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
        return 'LinkData';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();
        return GqlEntityRegistry::getOrCreate($typeName, fn() => new LinkData([
            'name' => $typeName,
            'fields' => fn() => Craft::$app->getGql()->prepareFieldDefinitions([
                'type' => Type::string(),
                'value' => Type::string(),
                'label' => Type::string(),
                'urlSuffix' => Type::string(),
                'url' => Type::string(),
                'link' => Type::string(),
                'target' => Type::string(),
                'title' => Type::string(),
                'class' => Type::string(),
                'id' => Type::string(),
                'rel' => Type::string(),
                'ariaLabel' => Type::string(),
                'elementType' => Type::string(),
                'elementId' => Type::int(),
                'elementSiteId' => Type::int(),
                'elementTitle' => Type::string(),
            ], $typeName),
        ]));
    }
}
