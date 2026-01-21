<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\base\Element;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final readonly class AddressesController
{
    use EditUserTrait;
    use PopulatesNames;
    use RespondsWithFlash;

    public function index(?int $userId = null): CpScreenResponse
    {
        $user = $this->editedUser($userId);

        $response = $this->asEditUserScreen($user, self::SCREEN_ADDRESSES);

        $response->contentHtml(function () use ($user) {
            $config = [
                'showInGrid' => true,
                'canCreate' => Gate::check('editUsers'),
            ];

            // Use an element index view if there's more than 50 addresses
            $total = Address::find()->owner($user)->count();
            if ($total > 50) {
                return $user->getAddressManager()->getIndexHtml($user, $config);
            }

            return Html::tag('h2', t('Addresses')).
                $user->getAddressManager()->getCardsHtml($user, $config);
        });

        return $response;
    }

    public function store(Request $request): Response
    {
        $elementsService = Craft::$app->getElements();
        $user = $request->user();

        $userId = (int) ($request->input('userId') ?? $user->id);
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

        abort_if(! $elementsService->canSave($address, $user), 403, 'User is not permitted to edit this address.');

        // Addresses have no status, and the default element save controller also sets the address scenario to live
        $address->setScenario(Element::SCENARIO_LIVE);

        // Name attributes
        $this->populateNameAttributes($request, $address);

        // All safe attributes
        $safeAttributes = [];
        foreach ($address->safeAttributes() as $name) {
            if (in_array($name, ['id', 'uid', 'ownerId'])) {
                continue;
            }

            $value = $request->input($name);
            if ($value !== null) {
                $safeAttributes[$name] = $value;
            }
        }

        $address->setAttributes($safeAttributes);

        // Custom fields
        $fieldsLocation = $request->input('fieldsLocation') ?? 'fields';
        $address->setFieldValuesFromRequest($fieldsLocation);

        if (! $elementsService->saveElement($address)) {
            return $this->asModelFailure($address, mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => Address::lowerDisplayName(),
            ])), 'address');
        }

        return $this->asModelSuccess($address, t('{type} saved.', [
            'type' => Address::displayName(),
        ]));
    }

    public function destroy(Request $request): Response
    {
        $request->validate([
            'addressId' => ['required', 'integer'],
        ]);

        $address = Address::findOne($addressId = $request->integer('addressId'));

        abort_if(! $address, 400, "Invalid address ID: $addressId");

        $elementsService = Craft::$app->getElements();

        abort_if(! $elementsService->canDelete($address), 403, 'User is not permitted to delete this address.');

        if (! $elementsService->deleteElement($address)) {
            return $this->asModelFailure($address, t('Couldn’t delete {type}.', [
                'type' => Address::lowerDisplayName(),
            ]), 'address');
        }

        return $this->asModelSuccess($address, t('{type} deleted.', [
            'type' => Address::displayName(),
        ]));
    }
}
