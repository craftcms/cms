<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\base\Volume;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use yii\db\Connection;

/**
 * AssetQuery represents a SELECT SQL statement for assets in a way that is independent of DBMS.
 *
 * @property string|string[]|Volume $volume The handle(s) of the volume(s) that resulting assets must belong to.
 * @method Asset[]|array all($db = null)
 * @method Asset|array|null one($db = null)
 * @method Asset|array|null nth(int $n, Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var int|int[]|null The volume ID(s) that the resulting assets must be in.
     * ---
     * ```php
     * // fetch assets in the Logos volume
     * $logos = \craft\elements\Asset::find()
     *     ->volume('logos')
     *     ->all();
     * ```
     * ```twig
     * {# fetch assets in the Logos volume #}
     * {% set logos = craft.assets()
     *     .volume('logos')
     *     .all() %}
     * ```
     * @used-by volume()
     * @used-by volumeId()
     */
    public $volumeId;

    /**
     * @var int|int[]|null The asset folder ID(s) that the resulting assets must be in.
     * @used-by folderId()
     */
    public $folderId;

    /**
     * @var string|string[]|null The filename(s) that the resulting assets must have.
     * @used-by filename()
     */
    public $filename;

    /**
     * @var string|string[]|null The file kind(s) that the resulting assets must be.
     *
     * Supported file kinds:
     * - access
     * - audio
     * - compressed
     * - excel
     * - flash
     * - html
     * - illustrator
     * - image
     * - javascript
     * - json
     * - pdf
     * - photoshop
     * - php
     * - powerpoint
     * - text
     * - video
     * - word
     * - xml
     * - unknown
     *
     * ---
     *
     * ```php
     * // fetch only images
     * $logos = \craft\elements\Asset::find()
     *     ->kind('image')
     *     ->all();
     * ```
     * ```twig
     * {# fetch only images #}
     * {% set logos = craft.assets()
     *     .kind('image')
     *     .all() %}
     * ```
     * @used-by kind()
     */
    public $kind;

    /**
     * @var int|null The width (in pixels) that the resulting assets must have.
     * ---
     * ```php{4}
     * // fetch images that are at least 500 pixels wide
     * $images = \craft\elements\Asset::find()
     *     ->kind('image')
     *     ->width('>= 500')
     *     ->all();
     * ```
     * ```twig{4}
     * {# fetch images that are at least 500 pixes wide #}
     * {% set logos = craft.assets()
     *     .kind('image')
     *     .width('>= 500')
     *     .all() %}
     * ```
     * @used-by width()
     */
    public $width;

    /**
     * @var int|null The height (in pixels) that the resulting assets must have.
     * ---
     * ```php{4}
     * // fetch images that are at least 500 pixels high
     * $images = \craft\elements\Asset::find()
     *     ->kind('image')
     *     ->height('>= 500')
     *     ->all();
     * ```
     * ```twig{4}
     * {# fetch images that are at least 500 pixes high #}
     * {% set logos = craft.assets()
     *     .kind('image')
     *     .height('>= 500')
     *     .all() %}
     * ```
     * @used-by height()
     */
    public $height;

    /**
     * @var int|null The size (in bytes) that the resulting assets must have.
     * @used-by size()
     */
    public $size;

    /**
     * @var mixed The Date Modified that the resulting assets must have.
     * @used-by dateModified()
     */
    public $dateModified;

    /**
     * @var bool Whether the query should search the subfolders of [[folderId]].
     * @used-by includeSubfolders()
     */
    public $includeSubfolders = false;

    /**
     * @var string|array|null The asset transform indexes that should be eager-loaded, if they exist
     * ---
     * ```php{4}
     * // fetch images with their 'thumb' transforms preloaded
     * $images = \craft\elements\Asset::find()
     *     ->kind('image')
     *     ->withTransforms(['thumb'])
     *     ->all();
     * ```
     * ```twig{4}
     * {# fetch images with their 'thumb' transforms preloaded #}
     * {% set logos = craft.assets()
     *     .kind('image')
     *     .withTransforms(['thumb'])
     *     .all() %}
     * ```
     * @used-by withTransforms()
     */
    public $withTransforms;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name === 'volume') {
            $this->volume($value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[$volumeId]] property based on a given volume(s)’s handle(s).
     *
     * @param string|string[]|Volume|null $value The property value
     * @return static self reference
     * @uses $volumeId
     */
    public function volume($value)
    {
        if ($value instanceof Volume) {
            $this->volumeId = $value->id;
        } else if ($value !== null) {
            $this->volumeId = (new Query())
                ->select(['id'])
                ->from(['{{%volumes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->volumeId = null;
        }

        return $this;
    }

    /**
     * Sets the [[$volumeId]] property based on a given volume(s)’s handle(s).
     *
     * @param string|string[]|Volume $value The property value
     * @return static self reference
     * @deprecated since Craft 3.0. Use [[volume()]] instead.
     */
    public function source($value)
    {
        Craft::$app->getDeprecator()->log('AssetQuery::source()', 'The “source” asset query param has been deprecated. Use “volume” instead.');

        return $this->volume($value);
    }

    /**
     * Sets the [[$volumeId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $volumeId
     */
    public function volumeId($value)
    {
        $this->volumeId = $value;
        return $this;
    }

    /**
     * Sets the [[$volumeId]] property.
     *
     * @param int|int[] $value The property value
     * @return static self reference
     * @deprecated since Craft 3.0. Use [[volumeId()]] instead.
     */
    public function sourceId($value)
    {
        Craft::$app->getDeprecator()->log('AssetQuery::sourceId()', 'The “sourceId” asset query param has been deprecated. Use “volumeId” instead.');

        return $this->volumeId($value);
    }

    /**
     * Sets the [[$folderId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     * @uses $folderId
     */
    public function folderId($value)
    {
        $this->folderId = $value;
        return $this;
    }

    /**
     * Sets the [[$filename]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $filename
     */
    public function filename($value)
    {
        $this->filename = $value;
        return $this;
    }

    /**
     * Sets the [[$kind]] property.
     *
     * @param string|string[]|null $value The property value
     * @return static self reference
     * @uses $kind
     */
    public function kind($value)
    {
        $this->kind = $value;
        return $this;
    }

    /**
     * Sets the [[$width]] property.
     *
     * @param int|null $value The property value
     * @return static self reference
     * @uses $width
     */
    public function width(int $value = null)
    {
        $this->width = $value;
        return $this;
    }

    /**
     * Sets the [[$height]] property.
     *
     * @param int|null $value The property value
     * @return static self reference
     * @uses $height
     */
    public function height(int $value = null)
    {
        $this->height = $value;
        return $this;
    }

    /**
     * Sets the [[$size]] property.
     *
     * @param int|null $value The property value
     * @return static self reference
     * @uses $size
     */
    public function size(int $value = null)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * Sets the [[$dateModified]] property.
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $dateModified
     */
    public function dateModified($value)
    {
        $this->dateModified = $value;
        return $this;
    }

    /**
     * Sets the [[$includeSubfolders]] property.
     *
     * @param bool $value The property value (defaults to true)
     * @return static self reference
     * @uses $includeSubfolders
     */
    public function includeSubfolders(bool $value = true)
    {
        $this->includeSubfolders = $value;
        return $this;
    }

    /**
     * Sets the [[$withTransforms]] property.
     *
     * @param string|array|null $value The transforms to include.
     * @return self The query object itself
     * @uses $withTransforms
     */
    public function withTransforms(array $value = null)
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
        if ($this->asArray === false && $this->withTransforms) {
            $transforms = $this->withTransforms;
            if (!is_array($transforms)) {
                $transforms = is_string($transforms) ? StringHelper::split($transforms) : [$transforms];
            }

            Craft::$app->getAssetTransforms()->eagerLoadTransforms($elements, $transforms);
        }

        return $elements;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        // See if 'source' was set to an invalid handle
        if ($this->volumeId === []) {
            return false;
        }

        $this->joinElementTable('assets');
        $this->query->innerJoin('{{%volumefolders}} volumeFolders', '[[assets.folderId]] = [[volumeFolders.id]]');

        $this->query->select([
            'assets.volumeId',
            'assets.folderId',
            'assets.filename',
            'assets.kind',
            'assets.width',
            'assets.height',
            'assets.size',
            'assets.focalPoint',
            'assets.dateModified',
            'volumeFolders.path AS folderPath'
        ]);

        if ($this->volumeId) {
            $this->subQuery->andWhere(Db::parseParam('assets.volumeId', $this->volumeId));
        }

        if ($this->folderId) {
            $folderCondition = Db::parseParam('assets.folderId', $this->folderId);
            if (is_numeric($this->folderId) && $this->includeSubfolders) {
                $assetsService = Craft::$app->getAssets();
                $descendants = $assetsService->getAllDescendantFolders($assetsService->getFolderById($this->folderId));
                $folderCondition = ['or', $folderCondition, ['in', 'assets.folderId', array_keys($descendants)]];
            }
            $this->subQuery->andWhere($folderCondition);
        }

        if ($this->filename) {
            $this->subQuery->andWhere(Db::parseParam('assets.filename', $this->filename));
        }

        if ($this->kind) {
            $this->subQuery->andWhere(Db::parseParam('assets.kind', $this->kind));
        }

        if ($this->width) {
            $this->subQuery->andWhere(Db::parseParam('assets.width', $this->width));
        }

        if ($this->height) {
            $this->subQuery->andWhere(Db::parseParam('assets.height', $this->height));
        }

        if ($this->size) {
            $this->subQuery->andWhere(Db::parseParam('assets.size', $this->size));
        }

        if ($this->dateModified) {
            $this->subQuery->andWhere(Db::parseDateParam('assets.dateModified', $this->dateModified));
        }

        return parent::beforePrepare();
    }
}
