<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\Address;
use craft\elements\Category;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use yii\db\Connection;

/**
 * AddressQuery represents a SELECT SQL statement for categories in a way that is independent of DBMS.
 *
 * @method Address[]|array all($db = null)
 * @method Address|array|null one($db = null)
 * @method Address|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @replace {element} address
 * @replace {elements} addresses
 * @replace {myElement} myAddress
 * @replace {element-class} \craft\elements\Address
 */
class AddressQuery extends ElementQuery
{
    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('addresses');

        $this->query->select([
            'addresses.id',
            'addresses.label',
            'addresses.countryCode',
            'addresses.givenName',
            'addresses.additionalName',
            'addresses.familyName',
            'addresses.addressLine1',
            'addresses.addressLine2',
            'addresses.administrativeArea',
            'addresses.locality',
            'addresses.dependentLocality',
            'addresses.postalCode',
            'addresses.sortingCode',
            'addresses.organization',
            'addresses.metadata',
        ]);

        return parent::beforePrepare();
    }

}
