<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use Craft;
use craft\base\Field;
use craft\gql\GqlEntityRegistry;
use craft\helpers\ArrayHelper;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Addresses
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class Addresses extends InputObjectType
{
    /**
     * Create the type for Addresses fields.
     *
     * @return mixed
     */
    public static function getType(): mixed
    {
        $typeName = 'AddressesInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                $fields = [
                    'id' => [
                        'name' => 'id',
                        'type' => Type::id(),
                    ],
                    'title' => [
                        'name' => 'title',
                        'type' => Type::string(),
                        'description' => 'The address label.',
                    ],
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
                    'addressLine3' => [
                        'name' => 'addressLine3',
                        'type' => Type::string(),
                        'description' => 'Third line of the address',
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
                ];

                // Get the field input types
                foreach (Craft::$app->getAddresses()->getFieldLayout()->getCustomFields() as $field) {
                    /** @var Field $field */
                    $fields[$field->handle] = $field->getContentGqlMutationArgumentType();
                }

                return $fields;
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize Matrix GraphQL input data to what Craft expects.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function normalizeValue(mixed $value): mixed
    {
        $preparedAddresses = [];
        $addressCounter = 1;

        if (!empty($value)) {
            $nativeFields = [
                'title',
                'fullName',
                'firstName',
                'lastName',
                'countryCode',
                'administrativeArea',
                'locality',
                'dependentLocality',
                'postalCode',
                'sortingCode',
                'addressLine1',
                'addressLine2',
                'organization',
                'organizationTaxId',
                'latitude',
                'longitude',
            ];

            foreach ($value as $addressData) {
                if (!empty($addressData)) {
                    $addressId = ArrayHelper::remove($addressData, 'id') ?? sprintf('new:%s', $addressCounter++);
                    $normalized = [];

                    foreach ($nativeFields as $field) {
                        if (array_key_exists($field, $addressData)) {
                        }
                        $normalized[$field] = ArrayHelper::remove($addressData, $field);
                    }

                    // Whatever's left must be custom fields
                    $normalized['fields'] = $addressData;

                    $preparedAddresses[$addressId] = $normalized;
                }
            }

            $value = $preparedAddresses;
        }

        return $value;
    }
}
