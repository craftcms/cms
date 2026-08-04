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
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\elements\Element;

/**
 * Class ElementType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class ElementType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        // Base elements have no context
        $type = static::generateType(null);
        return [$type->name => $type];
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = 'Element';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new Element([
            'name' => $typeName,
            'fields' => function() use ($typeName) {
                $elementFields = ElementInterface::getFieldDefinitions();
                return Craft::$app->getGql()->prepareFieldDefinitions($elementFields, $typeName);
            },
        ]));
    }
}
