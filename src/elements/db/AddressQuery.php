<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use craft\elements\Address;
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
     * @var string[]|string|null The address countryCode(s) that the resulting address must be in.
     * ---
     * ```php
     * // fetch addresses that are located in AU
     * $addresses = \craft\elements\Address::find()
     *     ->countryCode('AU')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses that are located in AU #}
     * {% set addresses = craft.addresses()
     *   .countryCode('AU')
     *   .all() %}
     * ```
     * @used-by countryCode()
     * @used-by countryCode()
     */
    public $countryCode;

    /**
     * Narrows the query results based on the country the assets belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'AU'` | with a countryCode of `AU`.
     * | `'not US'` | not in a countryCode of `US`.
     * | `['AU', 'US']` | in a countryCode of `AU` or `US`.
     * | `['not', 'AU', 'US']` | not in a countryCode of `AU` or `US`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in the AU #}
     * {% set {elements-var} = {twig-method}
     *   .countryCode('AU')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in the AU
     * ${elements-var} = {php-method}
     *     ->countryCode('AU')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return self self reference
     * @uses $countryCode
     */
    public function countryCode($value): self
    {
        $this->countryCode = $value;

        return $this;
    }

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
            'addresses.latitude',
            'addresses.longitude'
        ]);

        if ($this->countryCode) {
            $this->subQuery->andWhere(['addresses.countryCode' => $this->countryCode]);
        }

        return parent::beforePrepare();
    }

}
