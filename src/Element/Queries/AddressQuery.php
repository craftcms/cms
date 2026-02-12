<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements;
use CraftCms\Cms\Element\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @extends ElementQuery<Address>
 */
final class AddressQuery extends ElementQuery
{
    use QueriesNestedElements;

    /**
     * @var mixed The address countryCode(s) that the resulting address must be in.
     *            ---
     *            ```php
     *            // fetch addresses that are located in AU
     *            $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *            ->countryCode('AU')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch addresses that are located in AU #}
     *            {% set addresses = addresses()()
     *            .countryCode('AU')
     *            .all() %}
     *            ```
     *
     * @used-by countryCode()
     */
    public mixed $countryCode = null;

    /**
     * @var mixed Narrows the query results based on the administrative areas the addresses belongs to.
     *            ---
     *            ```php
     *            // fetch addresses that are located in Western Australia
     *            $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *            ->administrativeArea('WA')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch addresses that are located in Western Australia #}
     *            {% set addresses = addresses()()
     *            .administrativeArea('WA')
     *            .all() %}
     *            ```
     *
     * @used-by administrativeArea()
     */
    public mixed $administrativeArea = null;

    /**
     * @var string|null Narrows the query results based on the locality the addresses belong to.
     *                  ---
     *                  ```php
     *                  // fetch addresses by locality
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->locality('Perth')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by locality #}
     *                  {% set addresses = addresses()()
     *                  .locality('Perth')
     *                  .all() %}
     *                  ```
     *
     * @used-by locality()
     */
    public array|string|null $locality = null;

    /**
     * @var string|null Narrows the query results based on the dependent locality the addresses belong to.
     *                  ---
     *                  ```php
     *                  // fetch addresses by dependent locality
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->dependentLocality('Darlington')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by dependent locality #}
     *                  {% set addresses = addresses()()
     *                  .dependentLocality('Darlington')
     *                  .all() %}
     *                  ```
     *
     * @used-by dependentLocality()
     */
    public array|string|null $dependentLocality = null;

    /**
     * @var string|null Narrows the query results based on the postal code the addresses belong to.
     *                  ---
     *                  ```php
     *                  // fetch addresses by postal code
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->postalCode('10001')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by postal code #}
     *                  {% set addresses = addresses()()
     *                  .postalCode('10001')
     *                  .all() %}
     *                  ```
     *
     * @used-by postalCode()
     */
    public array|string|null $postalCode = null;

    /**
     * @var string|null Narrows the query results based on the sorting code the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by sorting code
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->sortingCode('ABCD')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by sorting code #}
     *                  {% set addresses = addresses()()
     *                  .sortingCode('ABCD')
     *                  .all() %}
     *                  ```
     *
     * @used-by sortingCode()
     */
    public array|string|null $sortingCode = null;

    /**
     * @var string|null Narrows the query results based on the organization the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by organization
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->organization('Pixel & Tonic')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by organization #}
     *                  {% set addresses = addresses()()
     *                  .organization('Pixel & Tonic')
     *                  .all() %}
     *                  ```
     *
     * @used-by organization()
     */
    public array|string|null $organization = null;

    /**
     * @var string|null Narrows the query results based on the tax ID the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by organization tax ID
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->organizationTaxId('123-456-789')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by organization tax ID #}
     *                  {% set addresses = addresses()()
     *                  .organizationTaxId('123-456-789')
     *                  .all() %}
     *                  ```
     *
     * @used-by organizationTaxId()
     */
    public array|string|null $organizationTaxId = null;

    /**
     * @var string|null Narrows the query results based on the first address line the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by address line 1
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->addressLine1('23 Craft st')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by address line 1 #}
     *                  {% set addresses = addresses()()
     *                  .addressLine1('23 Craft st')
     *                  .all() %}
     *                  ```
     *
     * @used-by addressLine1()
     */
    public array|string|null $addressLine1 = null;

    /**
     * @var string|null Narrows the query results based on the second address line the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by address line 2
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->addressLine2('Apt 5B')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by address line 2 #}
     *                  {% set addresses = addresses()()
     *                  .addressLine2('Apt 5B')
     *                  .all() %}
     *                  ```
     *
     * @used-by addressLine2()
     */
    public array|string|null $addressLine2 = null;

    /**
     * @var string|null Narrows the query results based on the third address line the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by address line 3
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->addressLine3('Suite 212')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by address line 3 #}
     *                  {% set addresses = addresses()()
     *                  .addressLine3('Suite 212')
     *                  .all() %}
     *                  ```
     *
     * @used-by addressLine3()
     */
    public array|string|null $addressLine3 = null;

    /**
     * @var string|null Narrows the query results based on the full name the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by full name
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->fullName('John Doe')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by full name #}
     *                  {% set addresses = addresses()()
     *                  .fullName('John Doe')
     *                  .all() %}
     *                  ```
     *
     * @used-by fullName()
     */
    public array|string|null $fullName = null;

    /**
     * @var string|null Narrows the query results based on the first name the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by first name
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->firstName('Doe')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by first name #}
     *                  {% set addresses = addresses()()
     *                  .firstName('Doe')
     *                  .all() %}
     *                  ```
     *
     * @used-by firstName()
     */
    public array|string|null $firstName = null;

    /**
     * @var string|null Narrows the query results based on the last name the addresses have.
     *                  ---
     *                  ```php
     *                  // fetch addresses by last name
     *                  $addresses = \CraftCms\Cms\Address\Elements\Address::find()
     *                  ->lastName('Doe')
     *                  ->all();
     *                  ```
     *                  ```twig
     *                  {# fetch addresses by last name #}
     *                  {% set addresses = addresses()()
     *                  .lastName('Doe')
     *                  .all() %}
     *                  ```
     *
     * @used-by lastName()
     */
    public array|string|null $lastName = null;

    public function getFieldIdColumn(): string
    {
        return 'addresses.fieldId';
    }

    public function getPrimaryOwnerIdColumn(): string
    {
        return 'addresses.primaryOwnerId';
    }

    public function shouldJoinElementsOwners(): bool
    {
        return ! empty($this->fieldId);
    }

    public function __construct(array $config = [])
    {
        parent::__construct(Address::class, $config);

        $this->joinElementTable(Table::ADDRESSES);

        $this->query->addSelect([
            'addresses.id as id',
            'addresses.fieldId as fieldId',
            'addresses.primaryOwnerId as primaryOwnerId',
            'addresses.countryCode as countryCode',
            'addresses.administrativeArea as administrativeArea',
            'addresses.locality as locality',
            'addresses.dependentLocality as dependentLocality',
            'addresses.postalCode as postalCode',
            'addresses.sortingCode as sortingCode',
            'addresses.addressLine1 as addressLine1',
            'addresses.addressLine2 as addressLine2',
            'addresses.addressLine3 as addressLine3',
            'addresses.organization as organization',
            'addresses.organizationTaxId as organizationTaxId',
            'addresses.fullName as fullName',
            'addresses.firstName as firstName',
            'addresses.lastName as lastName',
            'addresses.latitude as latitude',
            'addresses.longitude as longitude',
        ]);

        $this->beforeQuery(function (self $addressQuery) {
            $this->normalizeNestedElementParams($addressQuery);

            if (empty($addressQuery->fieldId) && (isset($addressQuery->primaryOwnerId) || isset($addressQuery->ownerId))) {
                // User addresses don't get rows in the elements_owners table
                if (! $addressQuery->primaryOwnerId && ! $addressQuery->ownerId) {
                    throw new QueryAbortedException;
                }

                $addressQuery->subQuery->whereIn('addresses.primaryOwnerId', Arr::wrap($addressQuery->primaryOwnerId ?? $addressQuery->ownerId));
            }

            foreach ([
                'countryCode',
                'administrativeArea',
                'locality',
                'dependentLocality',
                'postalCode',
                'sortingCode',
                'organization',
                'organizationTaxId',
                'addressLine1',
                'addressLine2',
                'addressLine3',
                'lastName',
                'firstName',
                'fullName',
            ] as $property) {
                if (! $addressQuery->$property) {
                    continue;
                }

                $addressQuery->subQuery->whereParam("addresses.$property", $addressQuery->$property);
            }
        });
    }

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
     * @uses $countryCode
     */
    public function countryCode(array|string|null $value): self
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
     * @uses $administrativeArea
     */
    public function administrativeArea(array|string|null $value): self
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
     * @uses $locality
     */
    public function locality(array|string|null $value): self
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
     * @uses $dependentLocality
     */
    public function dependentLocality(array|string|null $value): self
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
     * @uses $postalCode
     */
    public function postalCode(array|string|null $value): self
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
     * @uses $sortingCode
     */
    public function sortingCode(array|string|null $value): self
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
     * @uses $organization
     */
    public function organization(array|string|null $value): self
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
     * @uses $organizationTaxId
     */
    public function organizationTaxId(array|string|null $value): self
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
     * @uses $addressLine1
     */
    public function addressLine1(array|string|null $value): self
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
     * @uses $addressLine2
     */
    public function addressLine2(array|string|null $value): self
    {
        $this->addressLine2 = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the third address line the addresses have.
     *
     * Possible values include:
     *
     * | Value | Fetches addresses…
     * | - | -
     * | `'Suite 212'` | with an addressLine3 of `Suite 212`.
     * | `'*Suite*'` | with an addressLine3 containing `Suite`.
     * | `'Suite*'` | with an addressLine3 beginning with `Suite`.
     *
     * ---
     *
     * ```twig
     * {# Fetch addresses at Suite 212 #}
     * {% set {elements-var} = {twig-method}
     *   .addressLine3('Suite 212')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch addresses at Suite 212
     * ${elements-var} = {php-method}
     *     ->addressLine3('Suite 212')
     *     ->all();
     * ```
     *
     * @uses $addressLine3
     */
    public function addressLine3(array|string|null $value): self
    {
        $this->addressLine3 = $value;

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
     * @uses $fullName
     */
    public function fullName(array|string|null $value): self
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
     * @uses $firstName
     */
    public function firstName(array|string|null $value): self
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
     * @uses $lastName
     */
    public function lastName(array|string|null $value): self
    {
        $this->lastName = $value;

        return $this;
    }

    protected function fieldLayouts(): Collection
    {
        return new Collection([
            app(Addresses::class)->getFieldLayout(),
        ]);
    }
}
