<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Elements\User as UserInterface;
use CraftCms\Cms\Gql\Types\Elements\User;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\User\Elements\User as UserElement;

class UserType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        // Users have no context
        $type = static::generateType($context);

        return [$type->name => $type];
    }

    public static function generateType(mixed $context): ObjectType
    {
        return GqlEntityRegistry::getOrCreate(UserElement::GQL_TYPE_NAME, fn () => new User([
            'name' => UserElement::GQL_TYPE_NAME,
            'fields' => function () use ($context) {
                // Users don't have different types, so the context for a user will be the same every time.
                $context ??= Fields::getLayoutByType(UserElement::class);
                $contentFieldGqlTypes = self::getContentFields($context);
                $userFields = array_merge(UserInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                return Gql::prepareFieldDefinitions($userFields, UserElement::GQL_TYPE_NAME);
            },
        ]));
    }
}
