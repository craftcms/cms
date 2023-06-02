<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\elements\Address as AddressElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Address as AddressInterface;
use craft\gql\types\elements\Address;

/**
 * Class AddressType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AddressType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        // Users have no context
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $context = $context ?: Craft::$app->getFields()->getLayoutByType(AddressElement::class);
        $contentFieldGqlTypes = self::getContentFields($context);
        $addressFields = array_merge(AddressInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity(AddressElement::GQL_TYPE_NAME)
            ?: GqlEntityRegistry::createEntity(AddressElement::GQL_TYPE_NAME, new Address([
                'name' => AddressElement::GQL_TYPE_NAME,
                'fields' => fn() => Craft::$app->getGql()->prepareFieldDefinitions(
                    $addressFields,
                    AddressElement::GQL_TYPE_NAME,
                ),
            ]));
    }
}
