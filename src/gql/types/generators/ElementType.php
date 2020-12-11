<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use craft\base\Element as BaseElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\TypeManager;
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
    public static function generateTypes($context = null): array
    {
        // Base elements have no context
        $type = static::generateType(null);
        return [$type->name => $type];
    }

    /**
     * @inheritdoc
     */
    public static function generateType($context): ObjectType
    {
        $typeName = BaseElement::gqlTypeNameByContext(null);
        $elementFields = TypeManager::prepareFieldDefinitions(ElementInterface::getFieldDefinitions(), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Element([
            'name' => $typeName,
            'fields' => function() use ($elementFields) {
                return $elementFields;
            }
        ]));
    }
}
