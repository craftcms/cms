<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\Address;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use yii\base\InvalidArgumentException;
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
     * @var mixed The field ID(s) that the resulting addresses must belong to.
     * @used-by fieldId()
     * @since 5.0.0
     */
    public mixed $fieldId = null;

    /**
     * @var mixed The primary owner element ID(s) that the resulting addresses must belong to.
     * @used-by primaryOwner()
     * @used-by primaryOwnerId()
     * @since 5.0.0
     */
    public mixed $primaryOwnerId = null;

    /**
     * @var mixed The owner element ID(s) that the resulting addresses must belong to.
     * @used-by owner()
     * @used-by ownerId()
     */
    public mixed $ownerId = null;

    /**
     * @var bool|null Whether the owner elements can be drafts.
     * @used-by allowOwnerDrafts()
     * @since 5.0.0
     */
    public ?bool $allowOwnerDrafts = null;

    /**
     * @var bool|null Whether the owner elements can be revisions.
     * @used-by allowOwnerRevisions()
     * @since 5.0.0
     */
    public ?bool $allowOwnerRevisions = null;

    /**
     * @var ElementInterface|null The owner element specified by [[owner()]].
     * @used-by owner()
     */
    private ?ElementInterface $_owner = null;

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
     * @since 5.0.0
     */
    public mixed $countryCode = null;

    /**
     * @var mixed Narrows the query results based on the administrative areas the addresses belongs to.
     * ---
     * ```php
     * // fetch addresses that are located in Western Australia
     * $addresses = \craft\elements\Address::find()
     *     ->administrativeArea('WA')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses that are located in Western Australia #}
     * {% set addresses = craft.addresses()
     *   .administrativeArea('WA')
     *   .all() %}
     * ```
     * @used-by administrativeArea()
     * @since 5.0.0
     */
    public mixed $administrativeArea = null;

    /**
     * @var string|null Narrows the query results based on the locality the addresses belong to.
     * ---
     * ```php
     * // fetch addresses by locality
     * $addresses = \craft\elements\Address::find()
     *     ->locality('Perth')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by locality #}
     * {% set addresses = craft.addresses()
     *   .locality('Perth')
     *   .all() %}
     * ```
     * @used-by locality()
     * @since 5.0.0
     */
    public ?string $locality = null;

    /**
     * @var string|null Narrows the query results based on the dependent locality the addresses belong to.
     * ---
     * ```php
     * // fetch addresses by dependent locality
     * $addresses = \craft\elements\Address::find()
     *     ->dependentLocality('Darlington')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by dependent locality #}
     * {% set addresses = craft.addresses()
     *   .dependentLocality('Darlington')
     *   .all() %}
     * ```
     * @used-by dependentLocality()
     * @since 5.0.0
     */
    public ?string $dependentLocality = null;

    /**
     * @var string|null Narrows the query results based on the postal code the addresses belong to.
     * ---
     * ```php
     * // fetch addresses by postal code
     * $addresses = \craft\elements\Address::find()
     *     ->postalCode('10001')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by postal code #}
     * {% set addresses = craft.addresses()
     *   .postalCode('10001')
     *   .all() %}
     * ```
     * @used-by postalCode()
     * @since 5.0.0
     */
    public ?string $postalCode = null;

    /**
     * @var string|null Narrows the query results based on the sorting code the addresses have.
     * ---
     * ```php
     * // fetch addresses by sorting code
     * $addresses = \craft\elements\Address::find()
     *     ->sortingCode('ABCD')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by sorting code #}
     * {% set addresses = craft.addresses()
     *   .sortingCode('ABCD')
     *   .all() %}
     * ```
     * @used-by sortingCode()
     * @since 5.0.0
     */
    public ?string $sortingCode = null;

    /**
     * @var string|null Narrows the query results based on the organization the addresses have.
     * ---
     * ```php
     * // fetch addresses by organization
     * $addresses = \craft\elements\Address::find()
     *     ->organization('Pixel & Tonic')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by organization #}
     * {% set addresses = craft.addresses()
     *   .organization('Pixel & Tonic')
     *   .all() %}
     * ```
     * @used-by organization()
     * @since 5.0.0
     */
    public ?string $organization = null;

    /**
     * @var string|null Narrows the query results based on the tax ID the addresses have.
     * ---
     * ```php
     * // fetch addresses by organization tax ID
     * $addresses = \craft\elements\Address::find()
     *     ->organizationTaxId('123-456-789')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by organization tax ID #}
     * {% set addresses = craft.addresses()
     *   .organizationTaxId('123-456-789')
     *   .all() %}
     * ```
     * @used-by organizationTaxId()
     * @since 5.0.0
     */
    public ?string $organizationTaxId = null;


    /**
     * @var string|null Narrows the query results based on the first address line the addresses have.
     * ---
     * ```php
     * // fetch addresses by address line 1
     * $addresses = \craft\elements\Address::find()
     *     ->addressLine1('23 Craft st')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by address line 1 #}
     * {% set addresses = craft.addresses()
     *   .addressLine1('23 Craft st')
     *   .all() %}
     * ```
     * @used-by addressLine1()
     * @since 5.0.0
     */
    public ?string $addressLine1 = null;

    /**
     * @var string|null Narrows the query results based on the second address line the addresses have.
     * ---
     * ```php
     * // fetch addresses by address line 2
     * $addresses = \craft\elements\Address::find()
     *     ->addressLine2('Apt 5B')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by address line 2 #}
     * {% set addresses = craft.addresses()
     *   .addressLine2('Apt 5B')
     *   .all() %}
     * ```
     * @used-by addressLine2()
     * @since 5.0.0
     */
    public ?string $addressLine2 = null;

    /**
     * @var string|null Narrows the query results based on the full name the addresses have.
     * ---
     * ```php
     * // fetch addresses by full name
     * $addresses = \craft\elements\Address::find()
     *     ->fullName('John Doe')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by full name #}
     * {% set addresses = craft.addresses()
     *   .fullName('John Doe')
     *   .all() %}
     * ```
     * @used-by fullName()
     * @since 5.0.0
     */
    public ?string $fullName = null;

    /**
     * @var string|null Narrows the query results based on the first name the addresses have.
     * ---
     * ```php
     * // fetch addresses by first name
     * $addresses = \craft\elements\Address::find()
     *     ->firstName('Doe')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by first name #}
     * {% set addresses = craft.addresses()
     *   .firstName('Doe')
     *   .all() %}
     * ```
     * @used-by firstName()
     * @since 5.0.0
     */
    public ?string $firstName = null;

    /**
     * @var string|null Narrows the query results based on the last name the addresses have.
     * ---
     * ```php
     * // fetch addresses by last name
     * $addresses = \craft\elements\Address::find()
     *     ->lastName('Doe')
     *     ->all();
     * ```
     * ```twig
     * {# fetch addresses by last name #}
     * {% set addresses = craft.addresses()
     *   .lastName('Doe')
     *   .all() %}
     * ```
     * @used-by lastName()
     * @since 5.0.0
     */
    public ?string $lastName = null;

    /**
     * Narrows the query results based on the country the addresses belong to.
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
     * {# Fetch Australian addresses #}
     * {% set {elements-var} = {twig-method}
     *   .countryCode('AU')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Australian addresses
     * ${elements-var} = {php-method}
     *     ->countryCode('AU')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $countryCode
     */
    public function countryCode(array|string|null $value): static
    {
        $this->countryCode = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the administrative areas the addresses belongs to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'WA'` | with a administrative area of `WA`.
     * | `'not WA'` | not in a administrative area of `WA`.
     * | `['WA', 'SA']` | in a administrative area of `WA` or `SA`.
     * | `['not', 'WA', 'SA']` | not in a administrative area of `WA` or `SA`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in Western Australia #}
     * {% set {elements-var} = {twig-method}
     *   .administrativeArea('WA')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in Western Australia
     * ${elements-var} = {php-method}
     *     ->administrativeArea('WA')
     *     ->all();
     * ```
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $administrativeArea
     * @since 5.0.0
     */
    public function administrativeArea(array|string|null $value): static
    {
        $this->administrativeArea = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the locality the addresses belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Perth'` | with a locality of `Perth`.
     * | `'*Perth*'` | with a locality containing `Perth`.
     * | `'Ner*'` | with a locality beginning with `Per`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in Perth #}
     * {% set {elements-var} = {twig-method}
     *   .locality('Perth')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in Perth
     * ${elements-var} = {php-method}
     *     ->locality('Perth')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $locality
     * @since 5.0.0
     */
    public function locality(?string $value): static
    {
        $this->locality = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the dependent locality the addresses belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Darlington'` | with a dependentLocality of `Darlington`.
     * | `'*Darling*'` | with a dependentLocality containing `Darling`.
     * | `'Dar*'` | with a dependentLocality beginning with `Dar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in Darlington #}
     * {% set {elements-var} = {twig-method}
     *   .dependentLocality('Darlington')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in Darlington
     * ${elements-var} = {php-method}
     *     ->dependentLocality('Darlington')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $dependentLocality
     * @since 5.0.0
     */
    public function dependentLocality(?string $value): static
    {
        $this->dependentLocality = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the postal code the addresses belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'10001'` | with a postalCode of `10001`.
     * | `'*001*'` | with a postalCode containing `001`.
     * | `'100*'` | with a postalCode beginning with `100`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses with postal code 10001 #}
     * {% set {elements-var} = {twig-method}
     *   .postalCode('10001')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses with postal code 10001
     * ${elements-var} = {php-method}
     *     ->postalCode('10001')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $postalCode
     * @since 5.0.0
     */
    public function postalCode(?string $value): static
    {
        $this->postalCode = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the sorting code the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'ABCD'` | with a sortingCode of `ABCD`.
     * | `'*BC*'` | with a sortingCode containing `BC`.
     * | `'AB*'` | with a sortingCode beginning with `AB`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses with sorting code ABCD #}
     * {% set {elements-var} = {twig-method}
     *   .sortingCode('ABCD')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses with sorting code ABCD
     * ${elements-var} = {php-method}
     *     ->sortingCode('ABCD')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $sortingCode
     * @since 5.0.0
     */
    public function sortingCode(?string $value): static
    {
        $this->sortingCode = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the organization the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Pixel & Tonic'` | with an organization of `Pixel & Tonic`.
     * | `'*Pixel*'` | with an organization containing `Pixel`.
     * | `'Pixel*'` | with an organization beginning with `Pixel`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses for Pixel & Tonic #}
     * {% set {elements-var} = {twig-method}
     *   .organization('Pixel & Tonic')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses for Pixel & Tonic
     * ${elements-var} = {php-method}
     *     ->organization('Pixel & Tonic')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $organization
     * @since 5.0.0
     */
    public function organization(?string $value): static
    {
        $this->organization = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the tax ID the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'123-456-789'` | with an organizationTaxId of `123-456-789`.
     * | `'*456*'` | with an organizationTaxId containing `456`.
     * | `'123*'` | with an organizationTaxId beginning with `123`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses with tax ID 123-456-789 #}
     * {% set {elements-var} = {twig-method}
     *   .organizationTaxId('123-456-789')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses with tax ID 123-456-789
     * ${elements-var} = {php-method}
     *     ->organizationTaxId('123-456-789')
     *     ->all();
     * ```
     *
     * @param string $value The property value
     * @return static self reference
     * @uses $organizationTaxId
     * @since 5.0.0
     */
    public function organizationTaxId(string $value): static
    {
        $this->organizationTaxId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the first address line the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'23 Craft st'` | with a addressLine1 of `23 Craft st`.
     * | `'*23*'` | with a addressLine1 containing `23`.
     * | `'23*'` | with a addressLine1 beginning with `23`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses at 23 Craft st #}
     * {% set {elements-var} = {twig-method}
     *   .addressLine1('23 Craft st')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses at 23 Craft st
     * ${elements-var} = {php-method}
     *     ->addressLine1('23 Craft st')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $addressLine1
     * @since 5.0.0
     */
    public function addressLine1(?string $value): static
    {
        $this->addressLine1 = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the second address line the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Apt 5B'` | with an addressLine2 of `Apt 5B`.
     * | `'*5B*'` | with an addressLine2 containing `5B`.
     * | `'5B*'` | with an addressLine2 beginning with `5B`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses at Apt 5B #}
     * {% set {elements-var} = {twig-method}
     *   .addressLine2('Apt 5B')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses at Apt 5B
     * ${elements-var} = {php-method}
     *     ->addressLine2('Apt 5B')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $addressLine2
     * @since 5.0.0
     */
    public function addressLine2(?string $value): static
    {
        $this->addressLine2 = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the full name the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'John Doe'` | with a fullName of `John Doe`.
     * | `'*Doe*'` | with a fullName containing `Doe`.
     * | `'John*'` | with a fullName beginning with `John`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses for John Doe #}
     * {% set {elements-var} = {twig-method}
     *   .fullName('John Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses for John Doe
     * ${elements-var} = {php-method}
     *     ->fullName('John Doe')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $fullName
     * @since 5.0.0
     */
    public function fullName(?string $value): static
    {
        $this->fullName = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the first name the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'John'` | with a firstName of `John`.
     * | `'*Joh*'` | with a firstName containing `Joh`.
     * | `'Joh*'` | with a firstName beginning with `Joh`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses with first name John #}
     * {% set {elements-var} = {twig-method}
     *   .firstName('John')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses with first name John
     * ${elements-var} = {php-method}
     *     ->firstName('John')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $firstName
     * @since 5.0.0
     */
    public function firstName(?string $value): static
    {
        $this->firstName = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the last name the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Doe'` | with a lastName of `Doe`.
     * | `'*Do*'` | with a lastName containing `Do`.
     * | `'Do*'` | with a lastName beginning with `Do`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses with last name Doe #}
     * {% set {elements-var} = {twig-method}
     *   .lastName('Doe')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses with last name Doe
     * ${elements-var} = {php-method}
     *     ->lastName('Doe')
     *     ->all();
     * ```
     *
     * @param string|null $value The property value
     * @return static self reference
     * @uses $lastName
     * @since 5.0.0
     */
    public function lastName(?string $value): static
    {
        $this->lastName = $value;

        return $this;
    }


    /**
     * Narrows the query results based on the field the addresses are contained by.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a field with a handle of `foo`.
     * | `['foo', 'bar']` | in a field with a handle of `foo` or `bar`.
     * | an [[craft\fields\Addresses]] object | in a field represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo field #}
     * {% set {elements-var} = {twig-method}
     *   .field('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo field
     * ${elements-var} = {php-method}
     *     ->field('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $fieldId
     * @since 5.0.0
     */
    public function field(mixed $value): static
    {
        if (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getFields()->getFieldByHandle($item);
            }
            return $item instanceof ElementContainerFieldInterface ? $item->id : null;
        })) {
            $this->fieldId = $value;
        } else {
            $this->fieldId = false;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the field the addresses are contained by, per the fields’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `1` | in a field with an ID of 1.
     * | `'not 1'` | not in a field with an ID of 1.
     * | `[1, 2]` | in a field with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a field with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses in the field with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .fieldId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses in the field with an ID of 1
     * ${elements-var} = {php-method}
     *     ->fieldId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $fieldId
     * @since 5.0.0
     */
    public function fieldId(mixed $value): static
    {
        $this->fieldId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the primary owner element of the addresses, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `'not 1'` | not created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     * | `['not', 1, 2]` | not created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwnerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->primaryOwnerId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $primaryOwnerId
     * @since 5.0.0
     */
    public function primaryOwnerId(mixed $value): static
    {
        $this->primaryOwnerId = $value;
        return $this;
    }

    /**
     * Sets the [[primaryOwnerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses created for this entry
     * ${elements-var} = {php-method}
     *     ->primaryOwner($myEntry)
     *     ->all();
     * ```
     *
     * @param ElementInterface $primaryOwner The primary owner element
     * @return static self reference
     * @uses $primaryOwnerId
     * @since 5.0.0
     */
    public function primaryOwner(ElementInterface $primaryOwner): static
    {
        $this->primaryOwnerId = [$primaryOwner->id];
        $this->siteId = $primaryOwner->siteId;
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
     * @return static self reference
     * @uses $ownerId
     */
    public function owner(ElementInterface $owner): static
    {
        $this->ownerId = [$owner->id];
        $this->_owner = $owner;
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
     * @return static self reference
     * @uses $ownerId
     */
    public function ownerId(array|int|null $value): static
    {
        $this->ownerId = $value;
        $this->_owner = null;
        return $this;
    }

    /**
     * Narrows the query results based on whether the addresses’ owners are drafts.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `true` | which can belong to a draft.
     * | `false` | which cannot belong to a draft.
     *
     * @param bool|null $value The property value
     * @return static self reference
     * @uses $allowOwnerDrafts
     * @since 5.0.0
     */
    public function allowOwnerDrafts(?bool $value = true): static
    {
        $this->allowOwnerDrafts = $value;
        return $this;
    }

    /**
     * Narrows the query results based on whether the addresses’ owners are revisions.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `true` | which can belong to a revision.
     * | `false` | which cannot belong to a revision.
     *
     * @param bool|null $value The property value
     * @return static self reference
     * @uses $allowOwnerRevisions
     * @since 5.0.0
     */
    public function allowOwnerRevisions(?bool $value = true): static
    {
        $this->allowOwnerRevisions = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if (!parent::beforePrepare()) {
            return false;
        }

        if ($this->fieldId === false) {
            throw new QueryAbortedException();
        }

        $this->_normalizeFieldId();

        try {
            $this->primaryOwnerId = $this->_normalizeOwnerId($this->primaryOwnerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid primaryOwnerId param value');
        }

        try {
            $this->ownerId = $this->_normalizeOwnerId($this->ownerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid ownerId param value');
        }

        $this->joinElementTable(Table::ADDRESSES);

        $this->query->select([
            'addresses.id',
            'addresses.fieldId',
            'addresses.primaryOwnerId',
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

        if (!empty($this->fieldId) && (!empty($this->ownerId) || !empty($this->primaryOwnerId))) {
            // Join in the elements_owners table
            $ownersCondition = [
                'and',
                '[[elements_owners.elementId]] = [[elements.id]]',
                $this->ownerId ? ['elements_owners.ownerId' => $this->ownerId] : '[[elements_owners.ownerId]] = [[addresses.primaryOwnerId]]',
            ];

            $this->query
                ->addSelect([
                    'elements_owners.ownerId',
                    'elements_owners.sortOrder',
                ])
                ->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);
            $this->subQuery->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);

            $this->subQuery->andWhere(['addresses.fieldId' => $this->fieldId]);

            if ($this->primaryOwnerId) {
                $this->subQuery->andWhere(['addresses.primaryOwnerId' => $this->primaryOwnerId]);
            }

            // Ignore revision/draft blocks by default
            $allowOwnerDrafts = $this->allowOwnerDrafts ?? ($this->id || $this->primaryOwnerId || $this->ownerId);
            $allowOwnerRevisions = $this->allowOwnerRevisions ?? ($this->id || $this->primaryOwnerId || $this->ownerId);

            if (!$allowOwnerDrafts || !$allowOwnerRevisions) {
                $this->subQuery->innerJoin(
                    ['owners' => Table::ELEMENTS],
                    $this->ownerId ? '[[owners.id]] = [[elements_owners.ownerId]]' : '[[owners.id]] = [[addresses.primaryOwnerId]]'
                );

                if (!$allowOwnerDrafts) {
                    $this->subQuery->andWhere(['owners.draftId' => null]);
                }

                if (!$allowOwnerRevisions) {
                    $this->subQuery->andWhere(['owners.revisionId' => null]);
                }
            }

            $this->defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];
        } elseif (isset($this->ownerId)) {
            if (!$this->ownerId) {
                throw new QueryAbortedException();
            }
            $this->subQuery->andWhere(['addresses.primaryOwnerId' => $this->ownerId]);
        }

        if ($this->countryCode) {
            $this->subQuery->andWhere(Db::parseParam('addresses.countryCode', $this->countryCode));
        }

        if ($this->administrativeArea) {
            $this->subQuery->andWhere(Db::parseParam('addresses.administrativeArea', $this->administrativeArea));
        }

        if ($this->locality) {
            $this->subQuery->andWhere(Db::parseParam('addresses.locality', $this->locality));
        }

        if ($this->dependentLocality) {
            $this->subQuery->andWhere(Db::parseParam('addresses.dependentLocality', $this->dependentLocality));
        }

        if ($this->postalCode) {
            $this->subQuery->andWhere(Db::parseParam('addresses.postalCode', $this->postalCode));
        }

        if ($this->sortingCode) {
            $this->subQuery->andWhere(Db::parseParam('addresses.sortingCode', $this->sortingCode));
        }

        if ($this->organization) {
            $this->subQuery->andWhere(Db::parseParam('addresses.organization', $this->organization));
        }

        if ($this->organizationTaxId) {
            $this->subQuery->andWhere(Db::parseParam('addresses.organizationTaxId', $this->organizationTaxId));
        }

        if ($this->addressLine1) {
            $this->subQuery->andWhere(Db::parseParam('addresses.addressLine1', $this->addressLine1));
        }

        if ($this->addressLine2) {
            $this->subQuery->andWhere(Db::parseParam('addresses.addressLine2', $this->addressLine2));
        }

        if ($this->lastName) {
            $this->subQuery->andWhere(Db::parseParam('addresses.lastName', $this->lastName));
        }

        if ($this->firstName) {
            $this->subQuery->andWhere(Db::parseParam('addresses.firstName', $this->firstName));
        }

        if ($this->fullName) {
            $this->subQuery->andWhere(Db::parseParam('addresses.fullName', $this->fullName));
        }

        return true;
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     */
    private function _normalizeFieldId(): void
    {
        if (empty($this->fieldId)) {
            $this->fieldId = is_array($this->fieldId) ? [] : null;
        } elseif (is_numeric($this->fieldId)) {
            $this->fieldId = [$this->fieldId];
        } elseif (!is_array($this->fieldId) || !ArrayHelper::isNumeric($this->fieldId)) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseNumericParam('id', $this->fieldId))
                ->column();
        }
    }

    public function createElement(array $row): ElementInterface
    {
        if (isset($this->_owner)) {
            $row['owner'] = $this->_owner;
        }

        return parent::createElement($row);
    }

    /**
     * Normalizes the primaryOwnerId param to an array of IDs or null
     *
     * @param mixed $value
     * @return int[]|null
     * @throws InvalidArgumentException
     */
    private function _normalizeOwnerId(mixed $value): ?array
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return [$value];
        }
        if (!is_array($value) || !ArrayHelper::isNumeric($value)) {
            throw new InvalidArgumentException();
        }
        return $value;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->primaryOwnerId) {
            foreach ($this->primaryOwnerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }
        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "element::$ownerId";
            }
        }

        return $tags;
    }

    /**
     * @inheritdoc
     */
    protected function fieldLayouts(): array
    {
        return [
            Craft::$app->getAddresses()->getFieldLayout(),
        ];
    }
}
