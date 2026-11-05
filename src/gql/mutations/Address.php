<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\mutations;

use Craft;
use craft\gql\base\ElementMutationResolver;
use craft\gql\base\Mutation;
use craft\gql\base\MutationArguments;
use craft\gql\resolvers\mutations\Address as AddressMutationResolver;
use craft\gql\types\generators\AddressType;
use craft\gql\types\input\Addresses;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\Type;

/**
 * Class Address
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class Address extends Mutation
{
    /**
     * @inheritdoc
     */
    public static function getMutations(): array
    {
        $mutationList = [];

        if (GqlHelper::canSchema('addresses.all', 'save')) {
            /** @var AddressMutationResolver $resolver */
            $resolver = Craft::createObject(AddressMutationResolver::class);
            $customFields = Craft::$app->getAddresses()->getFieldLayout()->getCustomFields();
            static::prepareResolver($resolver, $customFields);

            $mutationList['save_Address'] = [
                'name' => 'save_Address',
                'description' => 'Save an address in a user’s address book. New addresses belong to the authenticated user unless `ownerId` is set.',
                'args' => array_merge(
                    MutationArguments::getArguments(),
                    self::attributeArguments(array_map(fn($field) => $field->handle, $customFields)),
                    $resolver->getResolutionData(ElementMutationResolver::CONTENT_FIELD_KEY),
                ),
                'resolve' => [$resolver, 'saveAddress'],
                'type' => AddressType::generateType(null),
            ];
        }

        if (GqlHelper::canSchema('addresses.all', 'delete')) {
            $mutationList['deleteAddress'] = [
                'name' => 'deleteAddress',
                'description' => 'Delete an address from a user’s address book.',
                'args' => [
                    'id' => Type::nonNull(Type::int()),
                ],
                'resolve' => [Craft::createObject(AddressMutationResolver::class), 'deleteAddress'],
                'type' => Type::boolean(),
            ];
        }

        return $mutationList;
    }

    /**
     * Returns the arguments for an address’s own attributes (its custom fields are added separately).
     *
     * They’re the same as the ones for addresses within a user’s `addresses` input, plus the owner.
     *
     * @param string[] $customFieldHandles
     * @return array
     */
    private static function attributeArguments(array $customFieldHandles): array
    {
        $arguments = [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'The ID of the user the address belongs to. Defaults to the authenticated user, and can’t be changed for existing addresses.',
            ],
        ];

        /** @var \GraphQL\Type\Definition\InputObjectType $inputType */
        $inputType = Addresses::getType();

        foreach ($inputType->getFields() as $name => $field) {
            if ($name === 'id' || in_array($name, $customFieldHandles, true)) {
                continue;
            }

            $arguments[$name] = $field->config;
        }

        // A country is only required for new addresses, which the resolver checks
        $arguments['countryCode']['type'] = Type::string();

        return $arguments;
    }
}
