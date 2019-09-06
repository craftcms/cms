<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\User as UserElement;
use craft\gql\base\GeneratorInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\User;

/**
 * Class UserType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class UserType implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = UserElement::gqlTypeNameByContext(null);

        $contentFields = Craft::$app->getFields()->getLayoutByType(UserElement::class)->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $userFields = array_merge(UserInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new User([
            'name' => $typeName,
            'fields' => function () use ($userFields) {
                return $userFields;
            }
        ]));


        return $gqlTypes;
    }
}
