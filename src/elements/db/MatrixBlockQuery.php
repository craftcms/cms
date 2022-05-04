<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\fields\Matrix as MatrixField;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\MatrixBlockType;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Connection;

/**
 * MatrixBlockQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @property-write ElementInterface $owner The owner element the Matrix blocks must belong to
 * @property-write ElementInterface $primaryOwner The primary owner element the Matrix blocks must belong to
 * @property-write string|string[]|MatrixBlockType|null $type The block type(s) that resulting Matrix blocks must have
 * @property-write string|string[]|MatrixField|null $field The field the Matrix blocks must belong to
 * @method MatrixBlock[]|array all($db = null)
 * @method MatrixBlock|array|null one($db = null)
 * @method MatrixBlock|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @doc-path matrix-blocks.md
 * @supports-site-params
 * @supports-status-param
 * @replace {element} Matrix block
 * @replace {elements} Matrix blocks
 * @replace {twig-method} craft.matrixBlocks()
 * @replace {myElement} myBlock
 * @replace {element-class} \craft\elements\MatrixBlock
 */
class MatrixBlockQuery extends ElementQuery
{
    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['matrixblocks_owners.sortOrder' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var mixed The field ID(s) that the resulting Matrix blocks must belong to.
     * @used-by fieldId()
     */
    public mixed $fieldId = null;

    /**
     * @var mixed The primary owner element ID(s) that the resulting Matrix blocks must belong to.
     * @used-by primaryOwner()
     * @used-by primaryOwnerId()
     * @since 4.0.0
     */
    public mixed $primaryOwnerId = null;

    /**
     * @var mixed The owner element ID(s) that the resulting Matrix blocks must belong to.
     * @used-by owner()
     * @used-by ownerId()
     */
    public mixed $ownerId = null;

    /**
     * @var bool|null Whether the owner elements can be drafts.
     * @used-by allowOwnerDrafts()
     * @since 3.3.10
     */
    public ?bool $allowOwnerDrafts = null;

    /**
     * @var bool|null Whether the owner elements can be revisions.
     * @used-by allowOwnerRevisions()
     * @since 3.3.10
     */
    public ?bool $allowOwnerRevisions = null;

    /**
     * @var mixed The block type ID(s) that the resulting Matrix blocks must have.
     * ---
     * ```php
     * // fetch the entry’s text blocks
     * $blocks = $entry->myMatrixField
     *     ->type('text')
     *     ->all();
     * ```
     * ```twig
     * {# fetch the entry’s text blocks #}
     * {% set blocks = entry.myMatrixField
     *   .type('text')
     *   .all() %}
     * ```
     * @used-by MatrixBlockQuery::type()
     * @used-by typeId()
     */
    public mixed $typeId = null;

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
            case 'type':
                $this->type($value);
                break;
            case 'ownerSite':
                Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSite()', 'The `ownerSite` Matrix block query param has been deprecated. Use `site` or `siteId` instead.');
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Narrows the query results based on the field the Matrix blocks belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a field with a handle of `foo`.
     * | `'not foo'` | not in a field with a handle of `foo`.
     * | `['foo', 'bar']` | in a field with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a field with a handle of `foo` or `bar`.
     * | a [[MatrixField]] object | in a field represented by the object.
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
     * @return self self reference
     * @uses $fieldId
     * @since 3.4.0
     */
    public function field(mixed $value): self
    {
        if (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getFields()->getFieldByHandle($item);
            }
            return $item instanceof MatrixField ? $item->id : null;
        })) {
            $this->fieldId = $value;
        } else {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseParam('handle', $value))
                ->andWhere(['type' => MatrixField::class])
                ->column();
        }

        return $this;
    }

    /**
     * Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `1` | in a field with an ID of 1.
     * | `'not 1'` | not in a field with an ID of 1.
     * | `[1, 2]` | in a field with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a field with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks in the field with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .fieldId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks in the field with an ID of 1
     * ${elements-var} = {php-method}
     *     ->fieldId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $fieldId
     */
    public function fieldId(mixed $value): self
    {
        $this->fieldId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the primary owner element of the Matrix blocks, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `'not 1'` | not created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     * | `['not', 1, 2]` | not created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwnerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->primaryOwnerId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $primaryOwnerId
     * @since 4.0.0
     */
    public function primaryOwnerId(mixed $value): self
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
     * {# Fetch Matrix blocks created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .primaryOwner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks created for this entry
     * ${elements-var} = {php-method}
     *     ->primaryOwner($myEntry)
     *     ->all();
     * ```
     *
     * @param ElementInterface $primaryOwner The primary owner element
     * @return self self reference
     * @uses $primaryOwnerId
     * @since 4.0.0
     */
    public function primaryOwner(ElementInterface $primaryOwner): self
    {
        $this->primaryOwnerId = [$primaryOwner->id];
        $this->siteId = $primaryOwner->siteId;
        return $this;
    }

    /**
     * Narrows the query results based on the owner element of the Matrix blocks, per the owners’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `'not 1'` | not created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     * | `['not', 1, 2]` | not created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .ownerId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $ownerId
     */
    public function ownerId(mixed $value): self
    {
        $this->ownerId = $value;
        return $this;
    }

    /**
     * Sets the [[ownerId()]] and [[siteId()]] parameters based on a given element.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks created for this entry #}
     * {% set {elements-var} = {twig-method}
     *   .owner(myEntry)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks created for this entry
     * ${elements-var} = {php-method}
     *     ->owner($myEntry)
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
        $this->siteId = $owner->siteId;
        return $this;
    }

    /**
     * Narrows the query results based on whether the Matrix blocks’ owners are drafts.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `true` | which can belong to a draft.
     * | `false` | which cannot belong to a draft.
     *
     * @param bool|null $value The property value
     * @return self self reference
     * @uses $allowOwnerDrafts
     * @since 3.3.10
     */
    public function allowOwnerDrafts(?bool $value = true): self
    {
        $this->allowOwnerDrafts = $value;
        return $this;
    }

    /**
     * Narrows the query results based on whether the Matrix blocks’ owners are revisions.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `true` | which can belong to a revision.
     * | `false` | which cannot belong to a revision.
     *
     * @param bool|null $value The property value
     * @return self self reference
     * @uses $allowOwnerDrafts
     * @since 3.3.10
     */
    public function allowOwnerRevisions(?bool $value = true): self
    {
        $this->allowOwnerRevisions = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the Matrix blocks’ block types.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `'foo'` | of a type with a handle of `foo`.
     * | `'not foo'` | not of a type with a handle of `foo`.
     * | `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
     * | an [[MatrixBlockType|MatrixBlockType]] object | of a type represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks with a Foo block type #}
     * {% set {elements-var} = myEntry.myMatrixField
     *   .type('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks with a Foo block type
     * ${elements-var} = $myEntry->myMatrixField
     *     ->type('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $typeId
     */
    public function type(mixed $value): self
    {
        if ($value instanceof MatrixBlockType) {
            $this->typeId = $value->id;
        } elseif ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from([Table::MATRIXBLOCKTYPES])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the Matrix blocks’ block types, per the types’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches Matrix blocks…
     * | - | -
     * | `1` | of a type with an ID of 1.
     * | `'not 1'` | not of a type with an ID of 1.
     * | `[1, 2]` | of a type with an ID of 1 or 2.
     * | `['not', 1, 2]` | not of a type with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch Matrix blocks of the block type with an ID of 1 #}
     * {% set {elements-var} = myEntry.myMatrixField
     *   .typeId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks of the block type with an ID of 1
     * ${elements-var} = $myEntry->myMatrixField
     *     ->typeId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $typeId
     */
    public function typeId(mixed $value): self
    {
        $this->typeId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function beforePrepare(): bool
    {
        $this->_normalizeFieldId();

        try {
            $this->primaryOwnerId = $this->_normalizeOwnerId($this->primaryOwnerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid ownerId param value');
        }

        try {
            $this->ownerId = $this->_normalizeOwnerId($this->ownerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid ownerId param value');
        }

        $this->joinElementTable('matrixblocks');

        // Join in the matrixblocks_owners table
        $ownersCondition = [
            'and',
            '[[matrixblocks_owners.blockId]] = [[elements.id]]',
            $this->ownerId ? ['matrixblocks_owners.ownerId' => $this->ownerId] : '[[matrixblocks_owners.ownerId]] = [[matrixblocks.primaryOwnerId]]',
        ];

        $this->query->innerJoin(['matrixblocks_owners' => Table::MATRIXBLOCKS_OWNERS], $ownersCondition);
        $this->subQuery->innerJoin(['matrixblocks_owners' => Table::MATRIXBLOCKS_OWNERS], $ownersCondition);

        // Figure out which content table to use
        $this->contentTable = null;
        if ($this->fieldId && count($this->fieldId) === 1) {
            /** @var MatrixField|null $matrixField */
            $matrixField = Craft::$app->getFields()->getFieldById(reset($this->fieldId));
            if ($matrixField) {
                $this->contentTable = $matrixField->contentTable;
            }
        }

        $this->query->addSelect([
            'matrixblocks.fieldId',
            'matrixblocks.primaryOwnerId',
            'matrixblocks.typeId',
            'matrixblocks_owners.ownerId',
            'matrixblocks_owners.sortOrder',
        ]);

        if ($this->fieldId) {
            $this->subQuery->andWhere(['matrixblocks.fieldId' => $this->fieldId]);
        }

        if ($this->primaryOwnerId) {
            $this->subQuery->andWhere(['matrixblocks.primaryOwnerId' => $this->primaryOwnerId]);
        }

        if (isset($this->typeId)) {
            // If typeId is an empty array, it's because type() was called but no valid type handles were passed in
            if (empty($this->typeId)) {
                return false;
            }

            $this->subQuery->andWhere(Db::parseNumericParam('matrixblocks.typeId', $this->typeId));
        }

        // Ignore revision/draft blocks by default
        $allowOwnerDrafts = $this->allowOwnerDrafts ?? ($this->id || $this->primaryOwnerId || $this->ownerId);
        $allowOwnerRevisions = $this->allowOwnerRevisions ?? ($this->id || $this->primaryOwnerId || $this->ownerId);

        if (!$allowOwnerDrafts || !$allowOwnerRevisions) {
            // todo: we will need to expand on this when Matrix blocks can be nested.
            $this->subQuery->innerJoin(
                ['owners' => Table::ELEMENTS],
                $this->ownerId ? '[[owners.id]] = [[matrixblocks_owners.ownerId]]' : '[[owners.id]] = [[matrixblocks.primaryOwnerId]]'
            );

            if (!$allowOwnerDrafts) {
                $this->subQuery->andWhere(['owners.draftId' => null]);
            }

            if (!$allowOwnerRevisions) {
                $this->subQuery->andWhere(['owners.revisionId' => null]);
            }
        }

        return parent::beforePrepare();
    }

    /**
     * Normalizes the fieldId param to an array of IDs or null
     *
     * @throws QueryAbortedException
     */
    private function _normalizeFieldId(): void
    {
        if (!isset($this->fieldId) && $this->id) {
            $this->fieldId = (new Query())
                ->select(['fieldId'])
                ->distinct()
                ->from([Table::MATRIXBLOCKS])
                ->where(Db::parseNumericParam('id', $this->id))
                ->column() ?: false;
        }

        if ($this->fieldId === false) {
            throw new QueryAbortedException();
        }

        if (empty($this->fieldId)) {
            $this->fieldId = null;
        } elseif (is_numeric($this->fieldId)) {
            $this->fieldId = [$this->fieldId];
        } elseif (!is_array($this->fieldId) || !ArrayHelper::isNumeric($this->fieldId)) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseNumericParam('id', $this->fieldId))
                ->andWhere(['type' => Matrix::class])
                ->column();
        }
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
     */
    protected function customFields(): array
    {
        // This method won't get called if $this->fieldId isn't set to a single int
        /** @var MatrixField $matrixField */
        $matrixField = Craft::$app->getFields()->getFieldById(reset($this->fieldId));

        if (!empty($this->typeId)) {
            $blockTypes = ArrayHelper::toArray($this->typeId);

            if (ArrayHelper::isNumeric($blockTypes)) {
                return $matrixField->getBlockTypeFields($blockTypes);
            }
        }

        return $matrixField->getBlockTypeFields();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];
        // If both the field and primary owner are set, then only tag the combos
        if ($this->fieldId && $this->primaryOwnerId) {
            foreach ($this->fieldId as $fieldId) {
                foreach ($this->primaryOwnerId as $primaryOwnerId) {
                    $tags[] = "field-owner:$fieldId-$primaryOwnerId";
                }
            }
        } else {
            if ($this->fieldId) {
                foreach ($this->fieldId as $fieldId) {
                    $tags[] = "field:$fieldId";
                }
            }
            if ($this->primaryOwnerId) {
                foreach ($this->primaryOwnerId as $primaryOwnerId) {
                    $tags[] = "owner:$primaryOwnerId";
                }
            }
        }
        return $tags;
    }
}
