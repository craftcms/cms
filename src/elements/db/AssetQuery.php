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
use craft\db\Table;
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
 * @supports-site-params
 * @supports-title-param
 * @replace {element} asset
 * @replace {elements} assets
 * @replace {twig-method} craft.assets()
 * @replace {myElement} myAsset
 * @replace {element-class} \craft\elements\Asset
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
     * @var mixed The width (in pixels) that the resulting assets must have.
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
     * @var mixed The height (in pixels) that the resulting assets must have.
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
     * @var mixed The size (in bytes) that the resulting assets must have.
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
     * Narrows the query results based on the volume the assets belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `'foo'` | in a volume with a handle of `foo`.
     * | `'not foo'` | not in a volume with a handle of `foo`.
     * | `['foo', 'bar']` | in a volume with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a volume with a handle of `foo` or `bar`.
     * | a [[Volume|Volume]] object | in a volume represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the Foo volume #}
     * {% set {elements-var} = {twig-method}
     *     .volume('foo')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} in the Foo group
     * ${elements-var} = {php-method}
     *     ->volume('foo')
     *     ->all();
     * ```
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
                ->from([Table::VOLUMES])
                ->where(Db::parseParam('handle', $value))
                ->andWhere(['dateDeleted' => null])
                ->column();
        } else {
            $this->volumeId = null;
        }

        return $this;
    }

    /**
     * Narrows the query results based on the volume the assets belong to.
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
     * Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `1` | in a volume with an ID of 1.
     * | `'not 1'` | not in a volume with an ID of 1.
     * | `[1, 2]` | in a volume with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a volume with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the volume with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .volumeId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the volume with an ID of 1
     * ${elements-var} = {php-method}
     *     ->volumeId(1)
     *     ->all();
     * ```
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
     * Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.
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
     * Narrows the query results based on the folders the assets belong to, per the folders’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches categories…
     * | - | -
     * | `1` | in a folder with an ID of 1.
     * | `'not 1'` | not in a folder with an ID of 1.
     * | `[1, 2]` | in a folder with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a folder with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the folder with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *     .folderId(1)
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the folder with an ID of 1
     * ${elements-var} = {php-method}
     *     ->folderId(1)
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: tip
     * This can be combined with [[includeSubfolders()]] if you want to include assets in all the subfolders of a certain folder.
     * :::
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
     * Narrows the query results based on the assets’ filenames.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo.jpg'` | with a filename of `foo.jpg`.
     * | `'foo*'` | with a filename that begins with `foo`.
     * | `'*.jpg'` | with a filename that ends with `.jpg`.
     * | `'*foo*'` | with a filename that contains `foo`.
     * | `'not *foo*'` | with a filename that doesn’t contain `foo`.
     * | `['*foo*', '*bar*'` | with a filename that contains `foo` or `bar`.
     * | `['not', '*foo*', '*bar*']` | with a filename that doesn’t contain `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the hi-res images #}
     * {% set {elements-var} = {twig-method}
     *     .filename('*@2x*')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch all the hi-res images
     * ${elements-var} = {php-method}
     *     ->filename('*@2x*')
     *     ->all();
     * ```
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
     * Narrows the query results based on the assets’ file kinds.
     *
     * Supported file kinds:
     * - `access`
     * - `audio`
     * - `compressed`
     * - `excel`
     * - `flash`
     * - `html`
     * - `illustrator`
     * - `image`
     * - `javascript`
     * - `json`
     * - `pdf`
     * - `photoshop`
     * - `php`
     * - `powerpoint`
     * - `text`
     * - `video`
     * - `word`
     * - `xml`
     * - `unknown`
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'image'` | with a file kind of `image`.
     * | `'not image'` | not with a file kind of `image`..
     * | `['image', 'pdf']` | with a file kind of `image` or `pdf`.
     * | `['not', 'image', 'pdf']` | not with a file kind of `image` or `pdf`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the images #}
     * {% set {elements-var} = {twig-method}
     *     .kind('image')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch all the images
     * ${elements-var} = {php-method}
     *     ->kind('image')
     *     ->all();
     * ```
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
     * Narrows the query results based on the assets’ image widths.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `100` | with a width of 100.
     * | `'>= 100'` | with a width of at least 100.
     * | `['>= 100', '<= 1000']` | with a width between 100 and 1,000.
     *
     * ---
     *
     * ```twig
     * {# Fetch XL images #}
     * {% set {elements-var} = {twig-method}
     *     .kind('image')
     *     .width('>= 1000')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch XL images
     * ${elements-var} = {php-method}
     *     ->kind('image')
     *     ->width('>= 1000')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $width
     */
    public function width($value)
    {
        $this->width = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ image heights.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `100` | with a height of 100.
     * | `'>= 100'` | with a height of at least 100.
     * | `['>= 100', '<= 1000']` | with a height between 100 and 1,000.
     *
     * ---
     *
     * ```twig
     * {# Fetch XL images #}
     * {% set {elements-var} = {twig-method}
     *     .kind('image')
     *     .height('>= 1000')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch XL images
     * ${elements-var} = {php-method}
     *     ->kind('image')
     *     ->height('>= 1000')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $height
     */
    public function height($value)
    {
        $this->height = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ file sizes (in bytes).
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `1000` | with a size of 1,000 bytes (1KB).
     * | `'< 1000000'` | with a size of less than 1,000,000 bytes (1MB).
     * | `['>= 1000', '< 1000000']` | with a size between 1KB and 1MB.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets that are smaller than 1KB #}
     * {% set {elements-var} = {twig-method}
     *     .size('< 1000')
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch assets that are smaller than 1KB
     * ${elements-var} = {php-method}
     *     ->size('< 1000')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return static self reference
     * @uses $size
     */
    public function size($value)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ files’ last-modified dates.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'>= 2018-04-01'` | that were modified on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were modified before 2018-05-01
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were modified between 2018-04-01 and 2018-05-01.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} modified in the last month #}
     * {% set start = date('30 days ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *     .dateModified(">= #{start}")
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} modified in the last month
     * $start = (new \DateTime('30 days ago'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateModified(">= {$start}")
     *     ->all();
     * ```
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
     * Broadens the query results to include assets from any of the subfolders of the folder specified by [[folderId()]].
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} in the folder with an ID of 1 (including its subfolders) #}
     * {% set {elements-var} = {twig-method}
     *     .folderId(1)
     *     .includeSubfolders()
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch categories in the folder with an ID of 1 (including its subfolders)
     * ${elements-var} = {php-method}
     *     ->folderId(1)
     *     ->includeSubfolders()
     *     ->all();
     * ```
     *
     * ---
     *
     * ::: warning
     * This will only work if [[folderId()]] was set to a single folder ID.
     * :::
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
     * Causes the query to return matching {elements} eager-loaded with image transform indexes.
     *
     * This can improve performance when displaying several image transforms at once, if the transforms
     * have already been generated.
     *
     * ---
     *
     * ```twig
     * {# Fetch {elements} with the 'thumbnail' and 'hiResThumbnail' transform data preloaded #}
     * {% set {elements-var} = {twig-method}
     *     .kind('image')
     *     .withTransforms(['thumbnail', 'hiResThumbnail'])
     *     .all() %}
     * ```
     *
     * ```php
     * // Fetch {elements} with the 'thumbnail' and 'hiResThumbnail' transform data preloaded
     * ${elements-var} = {php-method}
     *     ->kind('image')
     *     ->withTransforms(['thumbnail', 'hiResThumbnail'])
     *     ->all();
     * ```
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
            'assets.keptFile',
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
