<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;

/**
 * `owner`/`primaryOwner` are magic properties that resolve to `getOwner()`/`getPrimaryOwner()`
 * (via `NestedElement`). Since eager-loading those handles bypasses the generic
 * `Element::$_eagerLoadedElements` array (see `NestedElement::setEagerLoadedElements()`),
 * property access should always match the dedicated getters, whether or not the owner has been
 * eager-loaded.
 */
test('owner and primaryOwner property matches the dedicated getter', function (string $handle) {
    $address = new Address(['id' => 1]);

    // No owner set at all
    $getter = $handle === 'owner' ? $address->getOwner() : $address->getPrimaryOwner();
    expect($getter)->toBeNull()->and($address->$handle)->toBe($getter);

    // Owner set directly, not via eager-loading
    $owner = new User(['id' => 100]);
    $handle === 'owner' ? $address->setOwner($owner) : $address->setPrimaryOwner($owner);
    $getter = $handle === 'owner' ? $address->getOwner() : $address->getPrimaryOwner();
    expect($getter)->toBe($owner)->and($address->$handle)->toBe($getter);

    // Owner eager-loaded
    $eagerLoadedOwner = new Entry(['id' => 101]);
    $plan = new EagerLoadPlan(handle: $handle);
    $address2 = new Address(['id' => 2]);
    $address2->setEagerLoadedElements($handle, [$eagerLoadedOwner], $plan);
    $getter = $handle === 'owner' ? $address2->getOwner() : $address2->getPrimaryOwner();
    expect($getter)->toBe($eagerLoadedOwner)->and($address2->$handle)->toBe($getter);

    // No owner eager-loaded (empty result)
    $address3 = new Address(['id' => 3]);
    $address3->setEagerLoadedElements($handle, [], $plan);
    $getter = $handle === 'owner' ? $address3->getOwner() : $address3->getPrimaryOwner();
    expect($getter)->toBeNull()->and($address3->$handle)->toBe($getter);
})->with(['owner', 'primaryOwner']);
