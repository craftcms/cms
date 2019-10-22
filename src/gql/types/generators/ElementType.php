<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use craft\base\Element as BaseElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\elements\Element;

/**
 * Class ElementType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class ElementType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = BaseElement::gqlTypeNameByContext(null);

        $elementFields = ElementInterface::getFieldDefinitions();

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new Element([
            'name' => $typeName,
            'fields' => function() use ($elementFields) {
                return $elementFields;
            }
        ]));


        return $gqlTypes;
    }
}
