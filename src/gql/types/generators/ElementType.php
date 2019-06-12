<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Element as BaseElement;
use craft\gql\interfaces\elements\Element as ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\User;

/**
 * Class UserType
 */
class ElementType implements BaseGenerator
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = BaseElement::getGqlTypeNameByContext(null);

        $elementFields = ElementInterface::getFields();

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new User([
            'name' => $typeName,
            'fields' => function () use ($elementFields) {
                return $elementFields;
            }
        ]));


        return $gqlTypes;
    }
}
