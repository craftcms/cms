<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Policies;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Contracts\CraftUser;

class AddressPolicy extends ElementPolicy
{
    public function view(CraftUser $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('view', $owner);
    }

    public function save(CraftUser $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function delete(CraftUser $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function duplicate(CraftUser $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function copy(CraftUser $user, Address $address): bool
    {
        return $user->can('duplicate', $address);
    }

    public function createDrafts(CraftUser $user, Address $address): bool
    {
        return true;
    }

    private function getOwner(Address $address): ?ElementInterface
    {
        return $address->getOwner()?->getCanonical(true);
    }
}
