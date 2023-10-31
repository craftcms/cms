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
     * @return static self reference
     * @uses $administrativeArea
     */
    public function administrativeArea(array|string|null $value): static
    {
        $this->administrativeArea = $value;

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
     * @return static self reference
     * @uses $countryCode
     */
    public function countryCode(array|string|null $value): static
    {
        $this->countryCode = $value;

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
            $this->subQuery->andWhere(['addresses.countryCode' => $this->countryCode]);
        }

        if ($this->administrativeArea) {
            $this->subQuery->andWhere(['addresses.administrativeArea' => $this->administrativeArea]);
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

        return parent::createElement($row); // TODO: Change the autogenerated stub
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
