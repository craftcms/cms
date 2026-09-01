<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserAddressesViewModel;
use CraftCms\Cms\User\EditUserScreens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class AddressesController
{
    use EditUserTrait;
    use PopulatesNames;
    use RespondsWithFlash;

    public function index(?int $userId = null): CpScreenResponse
    {
        $user = $this->editedUser($userId);

        return $this->asEditUserScreen($user, EditUserScreens::ADDRESSES)
            ->inertiaPage('users/Addresses', new UserAddressesViewModel($user));
    }

    public function store(Request $request, Elements $elements): Response
    {
        $user = $request->craftUser();
        if (! $user) {
            abort(401);
        }

        $userId = (int) ($request->input('userId') ?? $user->getCraftUserId());
        $addressId = $request->input('addressId');

        if ($addressId) {
            $address = Address::findOne($addressId);

            abort_if(! $address, 400, "Invalid address ID: $addressId");
            abort_if($address->getOwnerId() !== $userId, 400, "Address $addressId is not owned by user $userId");
        } else {
            $address = new Address([
                'ownerId' => $userId,
            ]);
        }

        Gate::authorize('save', $address);

        // Addresses have no status, and the default element save controller also sets the address scenario to live
        $address->ruleset->useScenario(ElementRules::SCENARIO_LIVE);

        // Name attributes
        $this->populateNameAttributes($request, $address);

        // All safe attributes
        $safeAttributes = [];
        foreach ($address->safeAttributes() as $name) {
            if ($request->has($name)) {
                $safeAttributes[$name] = $request->input($name);
            }
        }

        $address->setAttributes($safeAttributes);

        // Custom fields
        $fieldsLocation = $request->input('fieldsLocation') ?? 'fields';
        $address->setFieldValuesFromRequest($fieldsLocation);

        if (! $elements->saveElement($address)) {
            return $this->asModelFailure($address, mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => Address::lowerDisplayName(),
            ])), 'address');
        }

        return $this->asModelSuccess($address, t('{type} saved.', [
            'type' => Address::displayName(),
        ]));
    }

    public function destroy(Request $request, Elements $elements): Response
    {
        $request->validate([
            'addressId' => ['required', 'integer'],
        ]);

        $address = Address::findOne($addressId = $request->integer('addressId'));

        abort_if(! $address, 400, "Invalid address ID: $addressId");

        Gate::authorize('delete', $address);

        if (! $elements->deleteElement($address)) {
            return $this->asModelFailure($address, t('Couldn’t delete {type}.', [
                'type' => Address::lowerDisplayName(),
            ]), 'address');
        }

        return $this->asModelSuccess($address, t('{type} deleted.', [
            'type' => Address::displayName(),
        ]));
    }
}
