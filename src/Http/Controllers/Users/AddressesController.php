<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use craft\elements\Address;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

final readonly class AddressesController
{
    use EditUserTrait;

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
}
