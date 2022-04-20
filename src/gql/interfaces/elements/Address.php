<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\interfaces\elements;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\types\generators\AddressType;
use craft\helpers\Gql;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends Element
{
    /**
     * @inheritdoc
     */
    public static function getTypeGenerator(): string
    {
        return AddressType::class;
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
            'description' => 'This is the interface implemented by all addresses.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        AddressType::generateTypes();

        return $type;
    }

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'AddressInterface';
    }

    /**
     * @inheritdoc
     */
    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), self::getConditionalFields(), [
            'fullName' => [
                'name' => 'fullName',
                'type' => Type::string(),
                'description' => 'The full name on the address.',
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::string(),
                'description' => 'The first name on the address.',
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::string(),
                'description' => 'The last name on the address.',
            ],
            'countryCode' => [
                'name' => 'countryCode',
                'type' => Type::nonNull(Type::string()),
                'description' => 'Two-letter country code',
            ],
            'administrativeArea' => [
                'name' => 'administrativeArea',
                'type' => Type::string(),
                'description' => 'Administrative area.',
            ],
            'locality' => [
                'name' => 'locality',
                'type' => Type::string(),
                'description' => 'Locality',
            ],
            'dependentLocality' => [
                'name' => 'dependentLocality',
                'type' => Type::string(),
                'description' => 'Dependent locality',
            ],
            'postalCode' => [
                'name' => 'postalCode',
                'type' => Type::string(),
                'description' => 'Postal code',
            ],
            'sortingCode' => [
                'name' => 'sortingCode',
                'type' => Type::string(),
                'description' => 'Sorting code',
            ],
            'addressLine1' => [
                'name' => 'addressLine1',
                'type' => Type::string(),
                'description' => 'First line of the address',
            ],
            'addressLine2' => [
                'name' => 'addressLine2',
                'type' => Type::string(),
                'description' => 'Second line of the address',
            ],
            'organization' => [
                'name' => 'organization',
                'type' => Type::string(),
                'description' => 'Organization name',
            ],
            'organizationTaxId' => [
                'name' => 'organizationTaxId',
                'type' => Type::string(),
                'description' => 'Organization tax ID',
            ],
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
