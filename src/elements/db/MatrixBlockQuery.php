<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\MatrixBlock;
use craft\fields\Matrix as MatrixField;
use craft\helpers\Db;
use craft\models\MatrixBlockType;
use craft\models\Site;
use yii\base\Exception;
use yii\db\Connection;

/**
 * MatrixBlockQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @property string|string[]|MatrixBlockType $type The handle(s) of the block type(s) that resulting Matrix blocks must have
 * @method MatrixBlock[]|array all($db = null)
 * @method MatrixBlock|array|null one($db = null)
 * @method MatrixBlock|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
    // Properties
    // =========================================================================

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
     * @deprecated in 3.2
     */
    public $ownerSiteId;

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

    // Public Methods
    // =========================================================================

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
     * Narrows the query results based on the field the Matrix blocks belong to, per the fields’ IDs.
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
     *     .fieldId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the field with an ID of 1
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
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | created for an element with an ID of 1.
     * | `'not 1'` | not created for an element with an ID of 1.
     * | `[1, 2]` | created for an element with an ID of 1 or 2.
     * | `['not', 1, 2]` | not created for an element with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} created for an element with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .ownerId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} created for an element with an ID of 1
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
     * @deprecated in 3.2.
     */
    public function ownerSiteId()
    {
        Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSiteId()', 'The “ownerSiteId” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
        return $this;
    }

    /**
     * @return static self reference
     * @deprecated in 3.2.
     */
    public function ownerSite()
    {
        Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerSite()', 'The “ownerSite” Matrix block query param has been deprecated. Use “site” or “siteId” instead.');
        return $this;
    }

    /**
     * @return static self reference
     * @deprecated in 3.0.
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
     * {# Fetch {elements} created for this entry #}
     * {% set {elements-var} = {twig-method}
     *     .owner(myEntry)
     *     .all() %}
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
     */
    public function owner(ElementInterface $owner)
    {
        /** @var Element $owner */
        $this->ownerId = $owner->id;
        $this->siteId = $owner->siteId;
        return $this;
    }

    /**
     * Narrows the query results based on the Matrix blocks’ block types.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
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
     * {# Fetch {elements} with a Foo block type #}
     * {% set {elements-var} = myEntry.myMatrixField
     *     .type('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} with a Foo block type
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
     * | Value | Fetches {elements}…
     * | - | -
     * | `1` | of a type with an ID of 1.
     * | `'not 1'` | not of a type with an ID of 1.
     * | `[1, 2]` | of a type with an ID of 1 or 2.
     * | `['not', 1, 2]` | not of a type with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} of the block type with an ID of 1 #}
     * {% set {elements-var} = myEntry.myMatrixField
     *     .typeId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} of the block type with an ID of 1
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('matrixblocks');

        // Figure out which content table to use
        $this->contentTable = null;

        if (!$this->fieldId && $this->id) {
            $fieldIds = (new Query())
                ->select(['fieldId'])
                ->distinct()
                ->from([Table::MATRIXBLOCKS])
                ->where(Db::parseParam('id', $this->id))
                ->column();

            $this->fieldId = count($fieldIds) === 1 ? $fieldIds[0] : $fieldIds;
        }

        if ($this->fieldId && is_numeric($this->fieldId)) {
            /** @var MatrixField $matrixField */
            $matrixField = Craft::$app->getFields()->getFieldById($this->fieldId);

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
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.fieldId', $this->fieldId));
        }

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.ownerId', $this->ownerId));
        }

        if ($this->typeId !== null) {
            // If typeId is an empty array, it's because type() was called but no valid type handles were passed in
            if (empty($this->typeId)) {
                return false;
            }

            $this->subQuery->andWhere(Db::parseParam('matrixblocks.typeId', $this->typeId));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function customFields(): array
    {
        // This method won't get called if $this->fieldId isn't set to a single int
        /** @var MatrixField $matrixField */
        $matrixField = Craft::$app->getFields()->getFieldById($this->fieldId);
        return $matrixField->getBlockTypeFields();
    }
}
