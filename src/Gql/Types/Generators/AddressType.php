<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Elements\Address as AddressInterface;
use CraftCms\Cms\Gql\Types\Elements\Address;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Gql;

class AddressType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        // Users have no context
        $type = static::generateType($context);

        return [$type->name => $type];
    }

    public static function generateType(mixed $context): ObjectType
    {
        return GqlEntityRegistry::getOrCreate(AddressElement::GQL_TYPE_NAME, fn () => new Address([
            'name' => AddressElement::GQL_TYPE_NAME,
            'fields' => function () use ($context) {
                $context ??= Fields::getLayoutByType(AddressElement::class);
                $contentFieldGqlTypes = self::getContentFields($context);
                $addressFields = array_merge(AddressInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                return Gql::prepareFieldDefinitions(
                    $addressFields,
                    AddressElement::GQL_TYPE_NAME
                );
            },
        ]));
    }
}
