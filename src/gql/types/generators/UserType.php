<?php
namespace craft\gql\types\generators;

use Craft;
use craft\base\Field;
use craft\elements\User as UserElement;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\User;

/**
 * Class UserType
 */
class UserType implements BaseGenerator
{
    /**
     * @inheritdoc
     */
    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = self::getName();

        $contentFields = Craft::$app->getFields()->getLayoutByType(UserElement::class)->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $userFields = array_merge(UserInterface::getFields(), $contentFieldGqlTypes);

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new User([
            'name' => $typeName,
            'fields' => function () use ($userFields) {
                return $userFields;
            }
        ]));


        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function getName($context = null): string
    {
        return 'User';
    }
}
