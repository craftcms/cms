<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces\Elements;

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Types\Generators\AddressType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Address extends Element
{
    #[Override]
    public static function getTypeGenerator(): string
    {
        return AddressType::class;
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
            'description' => 'This is the interface implemented by all addresses.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        AddressType::generateTypes();

        return $type;
    }

    #[Override]
    public static function getName(): string
    {
        return 'AddressInterface';
    }

    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            ...static::propertyFields(AddressElement::class, ['fullName', 'firstName', 'lastName', 'countryCode', 'administrativeArea', 'locality', 'dependentLocality', 'postalCode', 'sortingCode', 'addressLine1', 'addressLine2', 'addressLine3', 'organization', 'organizationTaxId']),
            'latitude' => [
                'name' => 'latitude',
                'type' => Type::string(),
                'description' => 'Latitude',
            ],
            'longitude' => [
                'name' => 'longitude',
                'type' => Type::string(),
                'description' => 'Longitude',
            ],
        ]), self::getName());
    }
}
