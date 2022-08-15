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
 * @property int|null $ownerId Owner ID
 * @property string $countryCode Country code
 * @property string|null $administrativeArea Administrative area
 * @property string|null $locality Locality
 * @property string|null $dependentLocality Dependent locality
 * @property string|null $postalCode Postal code
 * @property string|null $sortingCode Sorting code
 * @property string|null $addressLine1 First line of the address block
 * @property string|null $addressLine2 Second line of the address block
 * @property string|null $organization Organization name
 * @property string|null $organizationTaxId Organization tax ID
 * @property string|null $fullName Full name
 * @property string|null $firstName First name
 * @property string|null $lastName Last name
 * @property string|null $latitude Latitude
 * @property string|null $longitude Longitude
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
