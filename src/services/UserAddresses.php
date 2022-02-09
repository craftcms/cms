<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\models\Address;
use yii\base\Component;

/**
 * User Addresses service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUserAddresses()|`Craft::$app->userAddresses`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UserAddresses extends Component
{
    /**
     * @param int $userId
     * @return array
     */
    public function getAddressesByUserId(int $userId): array
    {
        $results = (new Query())
            ->select(['addressId'])
            ->from([Table::ADDRESSES_USERS])
            ->where([
                'userId' => $userId,
            ])
            ->column();

        $addresses = [];

        if (!empty($results)) {
            $addressResults = Craft::$app->getAddresses()->createAddressQuery()
                ->where(['id' => $results])
                ->all();

            foreach ($addressResults as $result) {
                $addresses[] = Craft::$app->getAddresses()->createAddress($result);
            }
        }

        return $addresses;
    }
}
