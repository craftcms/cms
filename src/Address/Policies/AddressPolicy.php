<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Policies;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Elements\User;

class AddressPolicy extends ElementPolicy
{
    public function view(User $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('view', $owner);
    }

    public function save(User $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function delete(User $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function duplicate(User $user, Address $address): bool
    {
        if (! $owner = $this->getOwner($address)) {
            return false;
        }

        return $user->can('save', $owner);
    }

    public function copy(User $user, Address $address): bool
    {
        return $user->can('duplicate', $address);
    }

    public function createDrafts(User $user, Address $address): bool
    {
        return true;
    }

    private function getOwner(Address $address): ?ElementInterface
    {
        return $address->getOwner()?->getCanonical(true);
    }
}
