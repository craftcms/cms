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
use yii\base\InvalidConfigException;
use yii\db\Connection;

/**
 * MatrixBlockQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @property string|string[]|MatrixBlockType $type The handle(s) of the block type(s) that resulting Matrix blocks must have
 * @method MatrixBlock[]|array all($db = null)
 * @method MatrixBlock|array|null one($db = null)
 * @method MatrixBlock|array|null nth(int $n, Connection $db = null)
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
    protected $defaultOrderBy = ['matrixblocks.sortOrder' => SORT_ASC];

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|int[]|string|false|null The field ID(s) that the resulting Matrix blocks must belong to.
     * @used-by fieldId()
     */
    public $fieldId;

    /**
     * @var int|int[]|null The owner element ID(s) that the resulting Matrix blocks must belong to.
     * @used-by owner()
     * @used-by ownerId()
     */
    public $ownerId;

    /**
     * @var mixed
     * @deprecated in 3.2.0
     */
    public $ownerSiteId;

    /**
     * @var bool|null Whether the owner elements can be drafts.
     * @used-by allowOwnerDrafts()
     * @since 3.3.10
     */
    public $allowOwnerDrafts;

    /**
     * @var bool|null Whether the owner elements can be revisions.
     * @used-by allowOwnerRevisions()
     * @since 3.3.10
     */
    public $allowOwnerRevisions;

    /**
     * @var int|int[]|null The block type ID(s) that the resulting Matrix blocks must have.
     * ---
     * ```php
     * // fetch the entry's text blocks
     * $blocks = $entry->myMatrixField
     *     ->type('text')
     *     ->all();
     * ```
     * ```twig
     * {# fetch the entry's text blocks #}
     * {% set blocks = entry.myMatrixField
     *     .type('text')
     *     .all() %}
     * ```
     * @used-by MatrixBlockQuery::type()
     * @used-by typeId()
     */
    public $typeId;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'ownerSite':
                Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSite()', 'The “ownerSite” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
                break;
            case 'type':
                $this->type($value);
                break;
            case 'ownerLocale':
                Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerLocale()', 'The “ownerLocale” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
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
     *     .field('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo field
     * ${elements-var} = {php-method}
     *     ->field('foo')
     *     ->all();
     * ```
     *
     * @param string|string[]|MatrixField|null $value The property value
     * @return static self reference
     * @uses $fieldId
     * @since 3.4.0
     */
    public function field($value)
    {
        if ($value instanceof MatrixField) {
            $this->fieldId = [$value->id];
        } else if (is_string($value) || (is_array($value) && count($value) === 1)) {
            if (!is_string($value)) {
                $value = reset($value);
            }
            $field = Craft::$app->getFields()->getFieldByHandle($value);
            if ($field && $field instanceof MatrixField) {
                $this->fieldId = [$field->id];
            } else {
                $this->fieldId = false;
            }
        } else if ($value !== null) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseParam('handle', $value))
                ->andWhere(['type' => MatrixField::class])
                ->column();
        } else {
            $this->fieldId = null;
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
     *     .fieldId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks in the field with an ID of 1
     * ${elements-var} = {php-method}
     *     ->fieldId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $fieldId
     */
    public function fieldId($value)
    {
        $this->fieldId = $value;
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
     *     .ownerId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks created for an element with an ID of 1
     * ${elements-var} = {php-method}
     *     ->ownerId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $ownerId
     */
    public function ownerId($value)
    {
        $this->ownerId = $value;
        return $this;
    }

    /**
     * @return static self reference
     * @deprecated in 3.2.0
     */
    public function ownerSiteId()
    {
        Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSiteId()', 'The “ownerSiteId” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
        return $this;
    }

    /**
     * @return static self reference
     * @deprecated in 3.2.0
     */
    public function ownerSite()
    {
        Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSite()', 'The “ownerSite” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
        return $this;
    }

    /**
     * @return static self reference
     * @deprecated in 3.0.0
     */
    public function ownerLocale()
    {
        Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerLocale()', 'The “ownerLocale” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
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
     *     .owner(myEntry)
     *     .all() %}
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
     * @return static self reference
     * @uses $ownerId
     */
    public function owner(ElementInterface $owner)
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
     * @return static self reference
     * @uses $allowOwnerDrafts
     * @since 3.3.10
     */
    public function allowOwnerDrafts($value = true)
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
     * @return static self reference
     * @uses $allowOwnerDrafts
     * @since 3.3.10
     */
    public function allowOwnerRevisions($value = true)
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
     *     .type('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks with a Foo block type
     * ${elements-var} = $myEntry->myMatrixField
     *     ->type('foo')
     *     ->all();
     * ```
     *
     * @param string|string[]|MatrixBlockType|null $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function type($value)
    {
        if ($value instanceof MatrixBlockType) {
            $this->typeId = $value->id;
        } else if ($value !== null) {
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
     *     .typeId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch Matrix blocks of the block type with an ID of 1
     * ${elements-var} = $myEntry->myMatrixField
     *     ->typeId(1)
     *     ->all();
     * ```
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $typeId
     */
    public function typeId($value)
    {
        $this->typeId = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->_normalizeFieldId();
        $this->joinElementTable('matrixblocks');

        // Figure out which content table to use
        $this->contentTable = null;
        if ($this->fieldId && count($this->fieldId) === 1) {
            /** @var MatrixField $matrixField */
            $matrixField = Craft::$app->getFields()->getFieldById(reset($this->fieldId));
            if ($matrixField) {
                $this->contentTable = $matrixField->contentTable;
            }
        }

        $this->query->select([
            'matrixblocks.fieldId',
            'matrixblocks.ownerId',
            'matrixblocks.typeId',
            'matrixblocks.sortOrder',
        ]);

        if ($this->fieldId) {
            $this->subQuery->andWhere(['matrixblocks.fieldId' => $this->fieldId]);
        }

        $this->_normalizeOwnerId();
        if ($this->ownerId) {
            $this->subQuery->andWhere(['matrixblocks.ownerId' => $this->ownerId]);
        }

        if ($this->typeId !== null) {
            // If typeId is an empty array, it's because type() was called but no valid type handles were passed in
            if (empty($this->typeId)) {
                return false;
            }

            $this->subQuery->andWhere(Db::parseParam('matrixblocks.typeId', $this->typeId));
        }

        // Ignore revision/draft blocks by default
        $allowOwnerDrafts = $this->allowOwnerDrafts ?? ($this->id || $this->ownerId);
        $allowOwnerRevisions = $this->allowOwnerRevisions ?? ($this->id || $this->ownerId);

        if (!$allowOwnerDrafts || !$allowOwnerRevisions) {
            // todo: we will need to expand on this when Matrix blocks can be nested.
            $this->subQuery->innerJoin(['owners' => Table::ELEMENTS], '[[owners.id]] = [[matrixblocks.ownerId]]');

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
    private function _normalizeFieldId()
    {
        if ($this->fieldId === null && $this->id) {
            $this->fieldId = (new Query())
                ->select(['fieldId'])
                ->distinct()
                ->from([Table::MATRIXBLOCKS])
                ->where(Db::parseParam('id', $this->id))
                ->column() ?: false;
        }

        if ($this->fieldId === false) {
            throw new QueryAbortedException();
        }

        if (empty($this->fieldId)) {
            $this->fieldId = null;
        } else if (is_numeric($this->fieldId)) {
            $this->fieldId = [$this->fieldId];
        } else if (!is_array($this->fieldId) || !ArrayHelper::isNumeric($this->fieldId)) {
            $this->fieldId = (new Query())
                ->select(['id'])
                ->from([Table::FIELDS])
                ->where(Db::parseParam('id', $this->fieldId))
                ->andWhere(['type' => Matrix::class])
                ->column();
        }
    }

    /**
     * Normalizes the ownerId param to an array of IDs or null
     *
     * @throws InvalidConfigException
     */
    private function _normalizeOwnerId()
    {
        if (empty($this->ownerId)) {
            $this->ownerId = null;
        } else if (is_numeric($this->ownerId)) {
            $this->ownerId = [$this->ownerId];
        } else if (!is_array($this->ownerId) || !ArrayHelper::isNumeric($this->ownerId)) {
            throw new InvalidConfigException('Invalid ownerId param value');
        }
    }

    /**
     * @inheritdoc
     */
    protected function customFields(): array
    {
        // This method won't get called if $this->fieldId isn't set to a single int
        /** @var MatrixField $matrixField */
        $matrixField = Craft::$app->getFields()->getFieldById(reset($this->fieldId));
        return $matrixField->getBlockTypeFields();
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];
        // If both the field and owner are set, then only tag the combos
        if ($this->fieldId && $this->ownerId) {
            foreach ($this->fieldId as $fieldId) {
                foreach ($this->ownerId as $ownerId) {
                    $tags[] = "field-owner:$fieldId-$ownerId";
                }
            }
        } else {
            if ($this->fieldId) {
                foreach ($this->fieldId as $fieldId) {
                    $tags[] = "field:$fieldId";
                }
            }
            if ($this->ownerId) {
                foreach ($this->ownerId as $ownerId) {
                    $tags[] = "owner:$ownerId";
                }
            }
        }
        return $tags;
    }
}
