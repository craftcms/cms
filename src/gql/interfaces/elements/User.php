<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\arguments\elements\Address as AddressArguments;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\resolvers\elements\Address as AddressResolver;
use craft\gql\types\generators\UserType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class User extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return UserType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all users.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        UserType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'UserInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
            'friendlyName' => [
                'name' => 'friendlyName',
                'type' => Type::string(),
                'description' => 'The user’s first name or username.',
            ],
            'fullName' => [
                'name' => 'fullName',
                'type' => Type::string(),
                'description' => 'The user’s full name.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The user’s full name or username.',
            ],
            'preferences' => [
                'name' => 'preferences',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The user’s preferences.',
                'complexity' => Gql::nPlus1Complexity(),
            ],
            'preferredLanguage' => [
                'name' => 'preferredLanguage',
                'type' => Type::string(),
                'description' => 'The user’s preferred language.',
                'complexity' => Gql::nPlus1Complexity(),
            ],
            'username' => [
                'name' => 'username',
                'type' => Type::string(),
                'description' => 'The username.',
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::string(),
                'description' => 'The user’s first name.',
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::string(),
                'description' => 'The user’s last name.',
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'description' => 'The user’s email.',
            ],
            'addresses' => [
                'name' => 'addresses',
                'args' => AddressArguments::getArguments(),
                'type' => Type::listOf(Address::getType()),
                'description' => 'The user’s addresses.',
                'complexity' => Gql::eagerLoadComplexity(),
                'resolve' => AddressResolver::class . '::resolve',
            ],
        ]), self::getName());
    }

    /**
     * @inheritdoc
     */
    protected static function getConditionalFields(): array
    {
        $volumeUid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');

        if (Gql::isSchemaAwareOf('volumes.' . $volumeUid)) {
            return [
                'photo' => [
                    'name' => 'photo',
                    'type' => Asset::getType(),
                    'description' => 'The user’s photo.',
                    'complexity' => Gql::eagerLoadComplexity(),
                ],
            ];
        }

        return [];
    }
}
