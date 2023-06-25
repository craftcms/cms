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
use craft\gql\types\elements\User;

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
        $typeName = AddressElement::gqlTypeNameByContext(null);

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new Address([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
                // Users don't have different types, so the context for a user will be the same every time.
                $context ??= Craft::$app->getFields()->getLayoutByType(AddressElement::class);
                $contentFieldGqlTypes = self::getContentFields($context);
                $addressFields = array_merge(AddressInterface::getFieldDefinitions(), $contentFieldGqlTypes);
                return Craft::$app->getGql()->prepareFieldDefinitions($addressFields, $typeName);
            },
        ]));
    }
}
