<?php
namespace craft\gql\interfaces\elements;

use craft\elements\User as UserElement;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\generators\UserType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class User
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
    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::class)) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::class, new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFields',
            'resolveType' => function (UserElement $value) {
                return GqlEntityRegistry::getEntity($value->getGqlTypeName());
            }
        ]));

        foreach (UserType::generateTypes() as $typeName => $generatedType) {
            TypeLoader::registerType($typeName, function () use ($generatedType) { return $generatedType ;});
        }

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
    public static function getFields(): array {
        return array_merge(parent::getCommonFields(), [
            'cooldownEndTime' => [
                'name' => 'cooldownEndTime',
                'type' => DateTime::getType(),
                'description' => 'The time when the user will be over their cooldown period.'
            ],
            'friendlyName' => [
                'name' => 'friendlyName',
                'type' => Type::string(),
                'description' => 'The user\'s first name or username'
            ],
            'fullName' => [
                'name' => 'fullName',
                'type' => Type::string(),
                'description' => 'The user\'s full name'
            ],
            'groupHandles' => [
                'name' => 'groupHandles',
                'type' => Type::listOf(Type::string()),
                'description' => 'groupHandles'
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The user\'s full name or username'
            ],
            'photo' => [
                'name' => 'photo',
                'type' => Asset::getType(),
                'description' => 'The user\'s photo'
            ],
            'preferences' => [
                'name' => 'preferences',
                'type' => Type::string(),
                'description' => 'The user’s preferences'
            ],
            'preferredLanguage' => [
                'name' => 'preferredLanguage',
                'type' => Type::string(),
                'description' => 'The user’s preferred language'
            ],
            'username' => [
                'name' => 'username',
                'type' => Type::string(),
                'description' => 'Username'
            ],
            'photoId' => [
                'name' => 'photoId',
                'type' => Type::int(),
                'description' => 'Photo asset id'
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::string(),
                'description' => 'First name'
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::string(),
                'description' => 'Last name'
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'description' => 'Email'
            ],
            'admin' => [
                'name' => 'admin',
                'type' => Type::boolean(),
                'description' => 'Admin'
            ],
            'locked' => [
                'name' => 'locked',
                'type' => Type::boolean(),
                'description' => 'Locked'
            ],
            'suspended' => [
                'name' => 'suspended',
                'type' => Type::boolean(),
                'description' => 'Suspended'
            ],
            'pending' => [
                'name' => 'pending',
                'type' => Type::boolean(),
                'description' => 'Pending'
            ],
            'lastLoginDate' => [
                'name' => 'lastLoginDate',
                'type' => DateTime::getType(),
                'description' => 'Last login date'
            ],
            'lastInvalidLoginDate' => [
                'name' => 'lastInvalidLoginDate',
                'type' => DateTime::getType(),
                'description' => 'Last invalid login date'
            ],
            'invalidLoginCount' => [
                'name' => 'invalidLoginCount',
                'type' => Type::int(),
                'description' => 'Invalid login count'
            ],
            'lockoutDate' => [
                'name' => 'lockoutDate',
                'type' => DateTime::getType(),
                'description' => 'Lockout date'
            ],
            'hasDashboard' => [
                'name' => 'hasDashboard',
                'type' => Type::boolean(),
                'description' => 'Whether the user has a dashboard'
            ],
            'passwordResetRequired' => [
                'name' => 'passwordResetRequired',
                'type' => Type::boolean(),
                'description' => 'Password reset required'
            ],
            'lastPasswordChangeDate' => [
                'name' => 'lastPasswordChangeDate',
                'type' => DateTime::getType(),
                'description' => 'Last password change date'
            ],
            'unverifiedEmail' => [
                'name' => 'unverifiedEmail',
                'type' => Type::string(),
                'description' => 'Unverified email'
            ],
            'verificationCodeIssuedDate' => [
                'name' => 'verificationCodeIssuedDate',
                'type' => DateTime::getType(),
                'description' => 'Verification code issued date'
            ],
            'verificationCode' => [
                'name' => 'verificationCode',
                'type' => Type::string(),
                'description' => 'Verification code'
            ],
            'lastLoginAttemptIp' => [
                'name' => 'lastLoginAttemptIp',
                'type' => Type::string(),
                'description' => 'Last login attempt IP address'
            ],
        ]);
    }
}
