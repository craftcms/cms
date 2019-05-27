<?php
namespace craft\gql\interfaces\elements;

use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\generators\UserType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class User
 */
class User extends BaseElement
{
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
            'resolveType' => function ($value) {
                return GqlEntityRegistry::getEntity(UserType::getName());
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
            'cooldownEndTime' => DateTime::getType(),
            'friendlyName' => Type::string(),
            'fullName' => Type::string(),
            'groupHandles' => Type::listOf(Type::string()),
            'name' => Type::string(),
            'photo' => Asset::getType(),
            'filename' => Type::string(),
            'preferences' => Type::string(),
            'preferredLanguage' => Type::string(),
            'username' => Type::string(),
            'photoId' => Type::int(),
            'firstName' => Type::string(),
            'lastName' => Type::string(),
            'email' => Type::string(),
            'admin' => Type::boolean(),
            'locked' => Type::boolean(),
            'suspended' => Type::boolean(),
            'pending' => Type::boolean(),
            'lastLoginDate' => DateTime::getType(),
            'lastInvalidLoginDate' => DateTime::getType(),
            'invalidLoginCounr' => Type::int(),
            'lockoutDate' => DateTime::getType(),
            'hasDashboard' => Type::boolean(),
            'passwordResetRequired' => Type::boolean(),
            'lastPasswordChangeDate' => DateTime::getType(),
            'unverifiedEmail' => Type::string(),
            'verificationCodeIssuedDate' => DateTime::getType(),
            'verificationCode' => Type::string(),
            'lastLoginAttemptIp' => Type::string(),
        ]);
    }
}
