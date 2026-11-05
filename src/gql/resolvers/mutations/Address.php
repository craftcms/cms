<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\mutations;

use Craft;
use craft\elements\Address as AddressElement;
use craft\elements\User;
use craft\gql\base\ElementMutationResolver;
use craft\helpers\Gql;
use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

/**
 * Class Address
 *
 * Saves and deletes the addresses in users’ address books. Who can do it is up to the address: users can change
 * their own, and anyone who can edit a user can change that user’s.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class Address extends ElementMutationResolver
{
    /**
     * Saves an address.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return AddressElement
     * @throws Throwable
     */
    public function saveAddress(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): AddressElement
    {
        $this->requireSchemaAction('addresses.all', 'save');

        $canIdentify = !empty($arguments['id']) || !empty($arguments['uid']);
        $ownerId = $arguments['ownerId'] ?? null;
        unset($arguments['ownerId']);

        if ($canIdentify) {
            $query = AddressElement::find();
            $address = (!empty($arguments['uid']) ? $query->uid($arguments['uid']) : $query->id($arguments['id']))->one();

            // The same error whether the address doesn’t exist, isn’t in an address book, or isn’t the user’s to change
            if (!$address || !$this->isUserAddress($address)) {
                throw new Error('Unable to perform the action.');
            }

            // Authorize before saying anything else about the address, such as who owns it
            $this->requireUserAuthorization($address, 'save');

            // An address stays with the user it was created for
            if ($ownerId && (int)$ownerId !== $address->getPrimaryOwnerId()) {
                throw new UserError('An address’s owner can’t be changed.');
            }
        } else {
            $owner = $this->resolveOwner($ownerId);

            $address = new AddressElement();
            $address->setPrimaryOwner($owner);
            $address->setOwner($owner);

            // Authorize before validating anything, so the errors can't be used to find out which users exist
            $this->requireUserAuthorization($address, 'save');

            if (empty($arguments['countryCode'])) {
                throw new UserError('A country code is required.');
            }
        }

        $address = $this->populateElementWithData($address, $arguments, $resolveInfo);
        $address = $this->saveElement($address);

        return AddressElement::find()->id($address->id)->one();
    }

    /**
     * Deletes an address.
     *
     * @param mixed $source
     * @param array $arguments
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return bool
     * @throws Throwable
     */
    public function deleteAddress(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): bool
    {
        $this->requireSchemaAction('addresses.all', 'delete');

        $address = AddressElement::find()->id($arguments['id'])->one();

        if (!$address) {
            return false;
        }

        if (!$this->isUserAddress($address)) {
            throw new Error('Unable to perform the action.');
        }

        $this->requireUserAuthorization($address, 'delete');

        return Craft::$app->getElements()->deleteElement($address);
    }

    /**
     * Returns the user a new address should belong to.
     *
     * @param int|string|null $ownerId
     * @return User
     * @throws Error
     * @throws UserError
     */
    private function resolveOwner(int|string|null $ownerId): User
    {
        $authenticatedUser = Gql::getSchemaUser();

        if (!$ownerId) {
            if (!$authenticatedUser) {
                throw new UserError('`ownerId` is required.');
            }

            return $authenticatedUser;
        }

        $owner = User::find()->id($ownerId)->status(null)->one();

        if (!$owner) {
            // Don't reveal which users exist to someone who can't edit them
            throw $authenticatedUser ? new Error('Unable to perform the action.') : new UserError('No user exists with that `ownerId`.');
        }

        return $owner;
    }

    /**
     * Returns whether an address is in a user’s address book, as opposed to belonging to something else, like an order.
     *
     * @param AddressElement $address
     * @return bool
     */
    private function isUserAddress(AddressElement $address): bool
    {
        return $address->fieldId === null && $address->getOwner() instanceof User;
    }
}
