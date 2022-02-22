<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;

/**
 * Class Address record.
 *
 * @property int $id ID
 * @property int $ownerId Owner ID
 * @property string $countryCode Country code
 * @property string $administrativeArea Administrative area
 * @property string $locality Locality
 * @property string $dependentLocality Dependent locality
 * @property string $postalCode Postal code
 * @property string $sortingCode Sorting code
 * @property string $addressLine1 First line of the address block
 * @property string $addressLine2 Second line of the address block
 * @property string $organization Organization name
 * @property string $organizationTaxId Organization tax ID
 * @property string $firstName First name
 * @property string $lastName Last name
 * @property string $latitude Latitude
 * @property string $longitude Longitude
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Address extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::ADDRESSES;
    }
}
