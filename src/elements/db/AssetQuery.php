<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\db;

use Craft;
use craft\app\base\Volume;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Db;

/**
 * AssetQuery represents a SELECT SQL statement for assets in a way that is independent of DBMS.
 *
 * @property string|string[]|Volume $volume The handle(s) of the volume(s) that resulting assets must belong to.
 *
 * @method Asset[]|array all($db = null)
 * @method Asset|array|null one($db = null)
 * @method Asset|array|null nth($n, $db = null)
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var integer|integer[] The volume ID(s) that the resulting assets must be in.
     */
    public $volumeId;

    /**
     * @var integer|integer[] The asset folder ID(s) that the resulting assets must be in.
     */
    public $folderId;

    /**
     * @var string|string[] The filename(s) that the resulting assets must have.
     */
    public $filename;

    /**
     * @var string|string[] The file kind(s) that the resulting assets must be.
     */
    public $kind;

    /**
     * @var integer|string The width (in pixels) that the resulting assets must have.
     */
    public $width;

    /**
     * @var integer|string The height (in pixels) that the resulting assets must have.
     */
    public $height;

    /**
     * @var integer|string The size (in bytes) that the resulting assets must have.
     */
    public $size;

    /**
     * @var mixed The Date Modified that the resulting assets must have.
     */
    public $dateModified;

    /**
     * @var boolean Whether the query should search the subfolders of [[folderId]].
     */
    public $includeSubfolders = false;

    /**
     * @var array The asset transform indexes that should be eager-loaded, if they exist
     */
    public $withTransforms;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'volume': {
                $this->volume($value);
                break;
            }
            default: {
                parent::__set($name, $value);
            }
        }
    }

    /**
     * Sets the [[volumeId]] property based on a given volume(s)’s handle(s).
     *
     * @param string|string[]|Volume $value The property value
     *
     * @return $this self reference
     */
    public function volume($value)
    {
        if ($value instanceof Volume) {
            $this->volumeId = $value->id;
        } else {
            $query = new Query();
            $this->volumeId = $query
                ->select('id')
                ->from('{{%volumes}}')
                ->where(Db::parseParam('handle', $value, $query->params))
                ->column();
        }

        return $this;
    }

    /**
     * Sets the [[volumeId]] property based on a given volume(s)’s handle(s).
     *
     * @param string|string[]|Volume $value The property value
     *
     * @return $this self reference
     * @deprecated since Craft 3.0. Use [[volume()]] instead.
     */
    public function source($value)
    {
        Craft::$app->getDeprecator()->log('AssetQuery::source()', 'The “source” asset parameter has been deprecated. Use “volume” instead.');

        return $this->volume($value);
    }

    /**
     * Sets the [[volumeId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     */
    public function volumeId($value)
    {
        $this->volumeId = $value;

        return $this;
    }

    /**
     * Sets the [[volumeId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     * @deprecated since Craft 3.0. Use [[volumeId()]] instead.
     */
    public function sourceId($value)
    {
        Craft::$app->getDeprecator()->log('AssetQuery::sourceId()', 'The “sourceId” asset parameter has been deprecated. Use “volumeId” instead.');

        return $this->volumeId($value);
    }

    /**
     * Sets the [[folderId]] property.
     *
     * @param integer|integer[] $value The property value
     *
     * @return $this self reference
     */
    public function folderId($value)
    {
        $this->folderId = $value;

        return $this;
    }

    /**
     * Sets the [[filename]] property.
     *
     * @param string|string[] $value The property value
     *
     * @return $this self reference
     */
    public function filename($value)
    {
        $this->filename = $value;

        return $this;
    }

    /**
     * Sets the [[kind]] property.
     *
     * @param string|string[] $value The property value
     *
     * @return $this self reference
     */
    public function kind($value)
    {
        $this->kind = $value;

        return $this;
    }

    /**
     * Sets the [[width]] property.
     *
     * @param integer|string $value The property value
     *
     * @return $this self reference
     */
    public function width($value)
    {
        $this->width = $value;

        return $this;
    }

    /**
     * Sets the [[height]] property.
     *
     * @param integer|string $value The property value
     *
     * @return $this self reference
     */
    public function height($value)
    {
        $this->height = $value;

        return $this;
    }

    /**
     * Sets the [[size]] property.
     *
     * @param integer|string $value The property value
     *
     * @return $this self reference
     */
    public function size($value)
    {
        $this->size = $value;

        return $this;
    }

    /**
     * Sets the [[dateModified]] property.
     *
     * @param mixed $value The property value
     *
     * @return $this self reference
     */
    public function dateModified($value)
    {
        $this->dateModified = $value;

        return $this;
    }

    /**
     * Sets the [[includeSubfolders]] property.
     *
     * @param boolean $value The property value (defaults to true)
     *
     * @return $this self reference
     */
    public function includeSubfolders($value = true)
    {
        $this->includeSubfolders = $value;

        return $this;
    }

    /**
     * Sets the [[withTransforms]] property.
     *
     * @param boolean $value The property value (defaults to true)
     *
     * @return self The query object itself
     */
    public function withTransforms($value)
    {
        $this->withTransforms = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function populate($rows)
    {
        $elements = parent::populate($rows);

        // Eager-load transforms?
        if (!$this->asArray && $this->withTransforms) {
            $transforms = ArrayHelper::toArray($this->withTransforms);

            Craft::$app->getAssetTransforms()->eagerLoadTransforms($elements, $transforms);
        }

        return $elements;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare()
    {
        // See if 'source' was set to an invalid handle
        if ($this->volumeId === []) {
            return false;
        }

        $this->joinElementTable('assets');
        $this->query->innerJoin('{{%volumefolders}} volumeFolders', 'assets.folderId = volumeFolders.id');

        $this->query->select([
            'assets.volumeId',
            'assets.folderId',
            'assets.filename',
            'assets.kind',
            'assets.width',
            'assets.height',
            'assets.size',
            'assets.dateModified',
            'volumeFolders.path AS folderPath'
        ]);

        if ($this->volumeId) {
            $this->subQuery->andWhere(Db::parseParam('assets.volumeId', $this->volumeId, $this->subQuery->params));
        }

        if ($this->folderId) {
            if ($this->includeSubfolders) {
                $folders = Craft::$app->getAssets()->getAllDescendantFolders(
                    Craft::$app->getAssets()->getFolderById($this->folderId));
                $this->subQuery->andWhere(Db::parseParam('assets.folderId', array_keys($folders), $this->subQuery->params));
            } else {
                $this->subQuery->andWhere(Db::parseParam('assets.folderId', $this->folderId, $this->subQuery->params));
            }
        }

        if ($this->filename) {
            $this->subQuery->andWhere(Db::parseParam('assets.filename', $this->filename, $this->subQuery->params));
        }

        if ($this->kind) {
            $this->subQuery->andWhere(Db::parseParam('assets.kind', $this->kind, $this->subQuery->params));
        }

        if ($this->width) {
            $this->subQuery->andWhere(Db::parseParam('assets.width', $this->width, $this->subQuery->params));
        }

        if ($this->height) {
            $this->subQuery->andWhere(Db::parseParam('assets.height', $this->height, $this->subQuery->params));
        }

        if ($this->size) {
            $this->subQuery->andWhere(Db::parseParam('assets.size', $this->size, $this->subQuery->params));
        }

        if ($this->dateModified) {
            $this->subQuery->andWhere(Db::parseDateParam('assets.dateModified', $this->dateModified, $this->subQuery->params));
        }

        return parent::beforePrepare();
    }
}
