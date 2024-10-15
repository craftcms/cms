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
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

/**
 * Trait NestedElementQueryTrait
 *
 * @mixin ElementQuery
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
trait NestedElementQueryTrait
{
    /**
     * @var mixed The field ID(s) that the resulting {elements} must belong to.
     * @used-by fieldId()
     * @since 5.0.0
     */
    public mixed $fieldId = null;

    /**
     * @var mixed The primary owner element ID(s) that the resulting {elements} must belong to.
     * @used-by primaryOwner()
     * @used-by primaryOwnerId()
     * @since 5.0.0
     */
    public mixed $primaryOwnerId = null;

    /**
     * @var mixed The owner element ID(s) that the resulting {elements} must belong to.
     * @used-by owner()
     * @used-by ownerId()
     * @since 5.0.0
     */
    public mixed $ownerId = null;

    /**
     * @var ElementInterface|null The owner element specified by [[owner()]].
     * @used-by owner()
     */
    private ?ElementInterface $_owner = null;

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
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'field':
                $this->field($value);
                break;
            case 'owner':
                $this->owner($value);
                break;
            case 'primaryOwner':
                $this->primaryOwner($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Narrows the query results based on the field the {elements} are contained by.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a field with a handle of `foo`.
     * | `['foo', 'bar']` | in a field with a handle of `foo` or `bar`.
     * | a [[craft\fields\Matrix]] object | in a field represented by the object.
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
     * Narrows the query results based on the field the {elements} are contained by, per the fields’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | in a field with an ID of 1.
     * | `'not 1'` | not in a field with an ID of 1.
     * | `[1, 2]` | in a field with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a field with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the field with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .fieldId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the field with an ID of 1
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
     * Narrows the query results based on the primary owner element of the {elements}, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwnerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
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
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for this entry
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
     * Narrows the query results based on the owner element of the {elements}, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .ownerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $ownerId
     * @since 5.0.0
     */
    public function ownerId(mixed $value): static
    {
        $this->ownerId = $value;
        $this->_owner = null;
        return $this;
    }

    /**
     * Sets the [[ownerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .owner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for this entry
     * ${elements-var} = {php-method}
     *     ->owner($myEntry)
     *     ->all();
     * ```
     *
     * @param ElementInterface $owner The owner element
     * @return static self reference
     * @uses $ownerId
     * @since 5.0.0
     */
    public function owner(ElementInterface $owner): static
    {
        $this->ownerId = [$owner->id];
        $this->siteId = $owner->siteId;
        $this->_owner = $owner;
        return $this;
    }

    /**
     * Narrows the query results based on whether the {elements}’ owners are drafts.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
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
     * Narrows the query results based on whether the {elements}’ owners are revisions.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
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

    private function applyNestedElementParams(string $fieldIdColumn, string $primaryOwnerIdColumn): void
    {
        $this->normalizeNestedElementParams();

        if ($this->fieldId === false || $this->primaryOwnerId === false || $this->ownerId === false) {
            throw new QueryAbortedException();
        }

        if (!empty($this->fieldId) || !empty($this->ownerId) || !empty($this->primaryOwnerId)) {
            // Join in the elements_owners table
            $ownersCondition = [
                'and',
                '[[elements_owners.elementId]] = [[elements.id]]',
                $this->ownerId ? ['elements_owners.ownerId' => $this->ownerId] : "[[elements_owners.ownerId]] = [[$primaryOwnerIdColumn]]",
            ];

            $this->query
                ->addSelect([
                    'elements_owners.ownerId',
                    'elements_owners.sortOrder',
                ])
                ->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);
            $this->subQuery->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);

            if ($this->fieldId) {
                $this->subQuery->andWhere([$fieldIdColumn => $this->fieldId]);
            }

            if ($this->primaryOwnerId) {
                $this->subQuery->andWhere([$primaryOwnerIdColumn => $this->primaryOwnerId]);
            }

            // Ignore revision/draft blocks by default
            $allowOwnerDrafts = $this->allowOwnerDrafts ?? ($this->id || $this->primaryOwnerId || $this->ownerId);
            $allowOwnerRevisions = $this->allowOwnerRevisions ?? ($this->id || $this->primaryOwnerId || $this->ownerId);

            if (!$allowOwnerDrafts || !$allowOwnerRevisions) {
                $this->subQuery->innerJoin(
                    ['owners' => Table::ELEMENTS],
                    $this->ownerId ? '[[owners.id]] = [[elements_owners.ownerId]]' : "[[owners.id]] = [[$primaryOwnerIdColumn]]"
                );

                if (!$allowOwnerDrafts) {
                    $this->subQuery->andWhere(['owners.draftId' => null]);
                }

                if (!$allowOwnerRevisions) {
                    $this->subQuery->andWhere(['owners.revisionId' => null]);
                }
            }

            $this->defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];
        }
    }

    /**
     * Normalizes the `fieldId`, `primaryOwnerId`, and `ownerId` params.
     */
    private function normalizeNestedElementParams(): void
    {
        $this->normalizeFieldId();
        $this->primaryOwnerId = $this->normalizeOwnerId($this->primaryOwnerId);
        $this->ownerId = $this->normalizeOwnerId($this->ownerId);
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     */
    private function normalizeFieldId(): void
    {
        if ($this->fieldId === false) {
            return;
        }

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

    /**
     * Normalizes the primaryOwnerId param to an array of IDs or null
     *
     * @param mixed $value
     * @return int[]|null|false
     */
    private function normalizeOwnerId(mixed $value): array|null|false
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            return [$value];
        }
        if (!is_array($value) || !ArrayHelper::isNumeric($value)) {
            return false;
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function createElement(array $row): ElementInterface
    {
        if (isset($this->_owner)) {
            $row['owner'] = $this->_owner;
        }

        return parent::createElement($row);
    }

    /**
     * @inheritdoc
     */
    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->fieldId) {
            foreach ($this->fieldId as $fieldId) {
                $tags[] = "field:$fieldId";
            }
        }

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
}
