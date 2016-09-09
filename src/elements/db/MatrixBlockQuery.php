<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\db\Query;
use craft\app\elements\MatrixBlock;
use craft\app\fields\Matrix as MatrixField;
use craft\app\helpers\Db;
use craft\app\models\MatrixBlockType;
use craft\app\models\Site;
use yii\base\Exception;

/**
 * MatrixBlockQuery represents a SELECT SQL statement for global sets in a way that is independent of DBMS.
 *
 * @property string|string[]|Site            $ownerSite The handle(s) of the site(s) that the owner element should be in
 * @property string|string[]|MatrixBlockType $type      The handle(s) of the block type(s) that resulting Matrix blocks must have
 *
 * @method MatrixBlock[]|array all($db = null)
 * @method MatrixBlock|array|null one($db = null)
 * @method MatrixBlock|array|null nth($n, $db = null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MatrixBlockQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public $orderBy = 'matrixblocks.sortOrder';

    /**
     * @var integer|integer[] The field ID(s) that the resulting Matrix blocks must belong to.
     */
    public $fieldId;

    /**
     * @var integer|integer[] The owner element ID(s) that the resulting Matrix blocks must belong to.
     */
    public $ownerId;

    /**
     * @var integer|integer[] The locale(s) that the resulting Matrix blocks must have been defined in.
     */
    public $ownerSiteId;

    /**
     * @var integer|integer[] The block type ID(s) that the resulting Matrix blocks must have.
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
                $this->ownerSite($value);
                break;
            case 'type': {
                $this->type($value);
                break;
            }
            case 'ownerLocale': {
                Craft::$app->getDeprecator()->log('MatrixBlockQuery::ownerLocale()', 'The “ownerLocale” element parameter has been deprecated. Use “ownerSite” or “ownerSiteId” instead.');
                $this->ownerSite($value);
                break;
            }
            default: {
                parent::__set($name, $value);
            }
        }
    }

    /**
     * Sets the [[fieldId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     */
    public function fieldId($value)
    {
        $this->fieldId = $value;

        return $this;
    }

    /**
     * Sets the [[ownerId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     */
    public function ownerId($value)
    {
        $this->ownerId = $value;

        return $this;
    }

    /**
     * Sets the [[ownerSiteId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     */
    public function ownerSiteId($value)
    {
        $this->ownerSiteId = $value;

        return $this;
    }

    /**
     * Sets the [[ownerSiteId]] property based on a given site(s)’s handle(s).
     *
     * @param string|string[]|Site $value The property value
     *
     * @return $this self reference
     * @throws Exception if $value is an invalid site handle
     */
    public function ownerSite($value)
    {
        if ($value instanceof Site) {
            $this->ownerSiteId = $value->id;
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if (!$site) {
                throw new Exception('Invalid site hadle: '.$value);
            }

            $this->ownerSiteId = $site->id;
        }

        return $this;
    }

    /**
     * Sets the [[ownerLocale]] property.
     *
     * @param string|string[] $value The property value
     *
     * @return $this self reference
     * @deprecated in 3.0. Use [[ownerSiteId()]] instead.
     */
    public function ownerLocale($value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element parameter has been deprecated. Use “site” or “siteId” instead.');
        $this->ownerSite($value);

        return $this;
    }

    /**
     * Sets the [[typeId]] property based on a given block type(s)’s handle(s).
     *
     * @param string|string[]|MatrixBlockType $value The property value
     *
     * @return $this self reference
     */
    public function type($value)
    {
        if ($value instanceof MatrixBlockType) {
            $this->typeId = $value->id;
        } else {
            $query = new Query();
            $this->typeId = $query
                ->select('id')
                ->from('{{%matrixblocktypes}}')
                ->where(Db::parseParam('handle', $value, $query->params))
                ->column();
        }

        return $this;
    }

    /**
     * Sets the [[typeId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
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
    protected function beforePrepare()
    {
        $this->joinElementTable('matrixblocks');

        // Figure out which content table to use
        $this->contentTable = null;

        if (!$this->fieldId && $this->id && is_numeric($this->id)) {
            $this->fieldId = (new Query())
                ->select('fieldId')
                ->from('{{%matrixblocks}}')
                ->where('id = :id', [':id' => $this->id])
                ->scalar();
        }

        if ($this->fieldId && is_numeric($this->fieldId)) {
            /** @var MatrixField $matrixField */
            $matrixField = Craft::$app->getFields()->getFieldById($this->fieldId);

            if ($matrixField) {
                $this->contentTable = Craft::$app->getMatrix()->getContentTableName($matrixField);
            }
        }

        $this->query->select([
            'matrixblocks.fieldId',
            'matrixblocks.ownerId',
            'matrixblocks.ownerSiteId',
            'matrixblocks.typeId',
            'matrixblocks.sortOrder',
        ]);

        if ($this->fieldId) {
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.fieldId', $this->fieldId, $this->subQuery->params));
        }

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.ownerId', $this->ownerId, $this->subQuery->params));
        }

        if ($this->ownerSiteId) {
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.ownerSiteId', $this->ownerSiteId, $this->subQuery->params));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(Db::parseParam('matrixblocks.typeId', $this->typeId, $this->subQuery->params));
        }

        return parent::beforePrepare();
    }
}
