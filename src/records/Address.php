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
 * @property string $countryCode The two-letter country code.
 * @property string $administrativeArea The administrative area.
 * @property string $locality The locality.
 * @property string $dependentLocality The dependent locality.
 * @property string $postalCode The postal code.
 * @property string $sortingCode The sorting code
 * @property string $addressLine1 The first line of the address block.
 * @property string $addressLine2 The second line of the address block.
 * @property string $organization The organization.
 * @property string $givenName The given name.
 * @property string $additionalName The additional name.
 * @property string $familyName The family name.
 * @property string $latitude The latitude of the address.
 * @property string $longitude The longitude of the address.
 * @property string $label The label to identify this address to the person who created it.
 * @property string $locale The locale. Defaults to 'und'.
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
