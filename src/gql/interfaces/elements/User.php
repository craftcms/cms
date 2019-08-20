<?php
namespace craft\gql\interfaces\elements;

use craft\elements\User as UserElement;
use craft\gql\interfaces\Element;
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
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all users.',
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
    public static function getFieldDefinitions(): array {
        return array_merge(parent::getFieldDefinitions(), [
            'cooldownEndTime' => [
                'name' => 'cooldownEndTime',
                'type' => DateTime::getType(),
                'description' => 'The time when the user will be over their cooldown period.'
            ],
            'friendlyName' => [
                'name' => 'friendlyName',
                'type' => Type::string(),
                'description' => 'The user\'s first name or username.'
            ],
            'fullName' => [
                'name' => 'fullName',
                'type' => Type::string(),
                'description' => 'The user\'s full name.'
            ],
            'groupHandles' => [
                'name' => 'groupHandles',
                'type' => Type::listOf(Type::string()),
                'description' => 'A list of all the user group handles that the user belongs to.'
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The user\'s full name or username.'
            ],
            'photo' => [
                'name' => 'photo',
                'type' => Asset::getType(),
                'description' => 'The user\'s photo.'
            ],
            'preferences' => [
                'name' => 'preferences',
                'type' => Type::string(),
                'description' => 'The user’s preferences.'
            ],
            'preferredLanguage' => [
                'name' => 'preferredLanguage',
                'type' => Type::string(),
                'description' => 'The user’s preferred language.'
            ],
            'username' => [
                'name' => 'username',
                'type' => Type::string(),
                'description' => 'The username.'
            ],
            'photoId' => [
                'name' => 'photoId',
                'type' => Type::int(),
                'description' => 'The photo asset id.'
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::string(),
                'description' => 'The user\'s first name.'
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::string(),
                'description' => 'The user\'s last name.'
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'description' => 'The user\'s email.'
            ],
            'admin' => [
                'name' => 'admin',
                'type' => Type::boolean(),
                'description' => 'Whether the user is an admin.'
            ],
            'locked' => [
                'name' => 'locked',
                'type' => Type::boolean(),
                'description' => 'Whether the user is locked.'
            ],
            'suspended' => [
                'name' => 'suspended',
                'type' => Type::boolean(),
                'description' => 'Whether the user is suspended.'
            ],
            'pending' => [
                'name' => 'pending',
                'type' => Type::boolean(),
                'description' => 'Whether the user is pending activation.'
            ],
            'lastLoginDate' => [
                'name' => 'lastLoginDate',
                'type' => DateTime::getType(),
                'description' => 'Last login date'
            ],
            'lastInvalidLoginDate' => [
                'name' => 'lastInvalidLoginDate',
                'type' => DateTime::getType(),
                'description' => 'Last invalid login date for the user.'
            ],
            'invalidLoginCount' => [
                'name' => 'invalidLoginCount',
                'type' => Type::int(),
                'description' => 'Invalid login count for the user.'
            ],
            'lockoutDate' => [
                'name' => 'lockoutDate',
                'type' => DateTime::getType(),
                'description' => 'The lockout date for the user.'
            ],
            'hasDashboard' => [
                'name' => 'hasDashboard',
                'type' => Type::boolean(),
                'description' => 'Whether the user has a dashboard or not.'
            ],
            'passwordResetRequired' => [
                'name' => 'passwordResetRequired',
                'type' => Type::boolean(),
                'description' => 'Whether a password reset required is required for the user.'
            ],
            'lastPasswordChangeDate' => [
                'name' => 'lastPasswordChangeDate',
                'type' => DateTime::getType(),
                'description' => 'Last password change date for the user.'
            ],
            'unverifiedEmail' => [
                'name' => 'unverifiedEmail',
                'type' => Type::string(),
                'description' => 'The unverified email for the user.'
            ],
            'verificationCodeIssuedDate' => [
                'name' => 'verificationCodeIssuedDate',
                'type' => DateTime::getType(),
                'description' => 'The date when the verification code was issued for the user.'
            ],
            'verificationCode' => [
                'name' => 'verificationCode',
                'type' => Type::string(),
                'description' => 'The verification code issued for the user.'
            ],
            'lastLoginAttemptIp' => [
                'name' => 'lastLoginAttemptIp',
                'type' => Type::string(),
                'description' => 'The last login attempt IP address for this user.'
            ],
        ]);
    }
}
