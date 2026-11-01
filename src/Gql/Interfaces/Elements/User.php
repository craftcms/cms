<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces\Elements;

use CraftCms\Cms\Gql\Arguments\Elements\Address as AddressArguments;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Resolvers\Elements\Address as AddressResolver;
use CraftCms\Cms\Gql\Types\Generators\UserType;
use CraftCms\Cms\Gql\Types\UserGroup;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User as UserElement;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class User extends Element
{
    #[Override]
    public static function getTypeGenerator(): string
    {
        return UserType::class;
    }

    #[Override]
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the interface implemented by all users.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        UserType::generateTypes();

        return $type;
    }

    #[Override]
    public static function getName(): string
    {
        return 'UserInterface';
    }

    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
            ...static::propertyFields(UserElement::class, ['friendlyName', 'fullName', 'name']),
            'preferences' => [
                'name' => 'preferences',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The user’s preferences.',
                'complexity' => GqlHelper::nPlus1Complexity(),
            ],
            'preferredLanguage' => [
                'name' => 'preferredLanguage',
                'type' => Type::string(),
                'description' => 'The user’s preferred language.',
                'complexity' => GqlHelper::nPlus1Complexity(),
            ],
            ...static::propertyFields(UserElement::class, ['username', 'firstName', 'lastName', 'email']),
            'affiliatedSiteHandle' => [
                'name' => 'affiliatedSiteHandle',
                'type' => Type::string(),
                'description' => 'The handle of the site the user is affiliated with.',
            ],
            ...static::propertyFields(UserElement::class, ['affiliatedSiteId']),
            'addresses' => [
                'name' => 'addresses',
                'args' => AddressArguments::getArguments(),
                'type' => Type::listOf(Address::getType()),
                'description' => 'The user’s addresses.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
                'resolve' => AddressResolver::class.'::resolve',
            ],
        ]), self::getName());
    }

    /** @return array<string, FieldDefinitionConfig> */
    protected static function getConditionalFields(): array
    {
        $fields = [];
        $volumeUid = ProjectConfig::get('users.photoVolumeUid');

        if (GqlHelper::isSchemaAwareOf('volumes.'.$volumeUid)) {
            $fields['photo'] = [
                'name' => 'photo',
                'type' => Asset::getType(),
                'description' => 'The user’s photo.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ];
        }

        if (GqlHelper::canSchema('settings.usergroups')) {
            $fields['groups'] = [
                'name' => 'groups',
                'type' => Type::nonNull(Type::listOf(Type::nonNull(UserGroup::getType()))),
                'description' => 'The user groups the user belongs to.',
                'complexity' => GqlHelper::nPlus1Complexity(),
            ];
        }

        return $fields;
    }
}
