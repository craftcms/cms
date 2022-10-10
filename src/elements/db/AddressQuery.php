<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use craft\base\ElementInterface;
use craft\db\QueryAbortedException;
use craft\elements\Address;
use craft\helpers\ArrayHelper;
use yii\base\InvalidConfigException;
use yii\db\Connection;

/**
 * AddressQuery represents a SELECT SQL statement for categories in a way that is independent of DBMS.
 *
 * @method Address[]|array all($db = null)
 * @method Address|array|null one($db = null)
 * @method Address|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @doc-path addresses.md
 * @replace {element} address
 * @replace {elements} addresses
 * @replace {twig-method} craft.addresses()
 * @replace {myElement} myAddress
 * @replace {element-class} \craft\elements\Address
 */
class AddressQuery extends ElementQuery
{
    /**
     * @var mixed The owner element ID(s) that the resulting addresses must belong to.
     * @used-by owner()
     * @used-by ownerId()
     */
    public mixed $ownerId = null;

    /**
     * @var mixed The address countryCode(s) that the resulting address must be in.
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
    public mixed $countryCode = null;

    /**
     * @var mixed The address administrativeArea(s) that the resulting address must be in.
     * ---
     * ```php
     * // fetch addresses that are located in AU
     * $addresses = \craft\elements\Address::find()
     *     ->administrativeArea('AU')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses that are located in AU #}
     * {% set addresses = craft.addresses()
     *   .administrativeArea('AU')
     *   .all() %}
     * ```
     * @used-by administrativeArea()
     * @used-by administrativeArea()
     */
    public mixed $administrativeArea = null;

    /**
     * Narrows the query results based on the administrative area the assets belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'AU'` | with a administrativeArea of `AU`.
     * | `'not US'` | not in a administrativeArea of `US`.
     * | `['AU', 'US']` | in a administrativeArea of `AU` or `US`.
     * | `['not', 'AU', 'US']` | not in a administrativeArea of `AU` or `US`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in the AU #}
     * {% set {elements-var} = {twig-method}
     *   .administrativeArea('AU')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in the AU
     * ${elements-var} = {php-method}
     *     ->administrativeArea('AU')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return self self reference
     * @uses $administrativeArea
     */
    public function administrativeArea(array|string|null $value): self
    {
        $this->administrativeArea = $value;

        return $this;
    }

    /**
     * Sets the [[ownerId()]] parameter based on a given owner element.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses for the current user #}
     * {% set {elements-var} = {twig-method}
     *   .owner(currentUser)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses created for the current user
     * ${elements-var} = {php-method}
     *     ->owner(Craft::$app->user->identity)
     *     ->all();
     * ```
     *
     * @param ElementInterface $owner The owner element
     * @return self self reference
     * @uses $ownerId
     */
    public function owner(ElementInterface $owner): self
    {
        $this->ownerId = [$owner->id];
        return $this;
    }

    /**
     * Narrows the query results based on the addresses’ owner elements, per their IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .ownerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return self self reference
     * @uses $ownerId
     */
    public function ownerId(array|int|null $value): self
    {
        $this->ownerId = $value;
        return $this;
    }

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
    public function countryCode(array|string|null $value): self
    {
        $this->countryCode = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->_normalizeOwnerId();

        $this->joinElementTable('addresses');

        $this->query->select([
            'addresses.id',
            'addresses.ownerId',
            'addresses.countryCode',
            'addresses.administrativeArea',
            'addresses.locality',
            'addresses.dependentLocality',
            'addresses.postalCode',
            'addresses.sortingCode',
            'addresses.addressLine1',
            'addresses.addressLine2',
            'addresses.organization',
            'addresses.organizationTaxId',
            'addresses.fullName',
            'addresses.firstName',
            'addresses.lastName',
            'addresses.latitude',
            'addresses.longitude',
        ]);

        if (isset($this->ownerId)) {
            if (!$this->ownerId) {
                throw new QueryAbortedException();
            }
            $this->subQuery->andWhere(['addresses.ownerId' => $this->ownerId]);
        }

        if ($this->countryCode) {
            $this->subQuery->andWhere(['addresses.countryCode' => $this->countryCode]);
        }

        if ($this->administrativeArea) {
            $this->subQuery->andWhere(['addresses.administrativeArea' => $this->administrativeArea]);
        }

        return parent::beforePrepare();
    }

    /**
     * Normalizes the ownerId param to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function _normalizeOwnerId(): void
    {
        if ($this->ownerId === null) {
            return;
        }
        if (is_numeric($this->ownerId)) {
            $this->ownerId = [$this->ownerId];
        }
        if (!is_array($this->ownerId) || !ArrayHelper::isNumeric($this->ownerId)) {
            throw new InvalidConfigException();
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "owner:$ownerId";
            }
        }

        return $tags;
    }
}
