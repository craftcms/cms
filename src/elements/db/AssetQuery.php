<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\db;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Volume;
use yii\base\InvalidArgumentException;
use yii\db\Connection;
use yii\db\Schema;

/**
 * AssetQuery represents a SELECT SQL statement for assets in a way that is independent of DBMS.
 *
 * @property-write string|string[]|Volume|null $volume The volume(s) that resulting assets must belong to
 * @method Asset[]|array all($db = null)
 * @method Asset|array|null one($db = null)
 * @method Asset|array|null nth(int $n, ?Connection $db = null)
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @doc-path assets.md
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
    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool|null Whether to only return assets that the user has permission to view.
     * @used-by editable()
     * @since 4.4.0
     */
    public ?bool $editable = null;

    /**
     * @var bool|null Whether to only return entries that the user has permission to save.
     * @used-by savable()
     * @since 4.4.0
     */
    public ?bool $savable = null;

    /**
     * @var mixed The volume ID(s) that the resulting assets must be in.
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
     *   .volume('logos')
     *   .all() %}
     * ```
     * @used-by volume()
     * @used-by volumeId()
     */
    public mixed $volumeId = null;

    /**
     * @var mixed The asset folder ID(s) that the resulting assets must be in.
     * @used-by folderId()
     */
    public mixed $folderId = null;

    /**
     * @var int|null The user ID that the resulting assets must have been uploaded by.
     * @used-by uploader()
     * @since 3.4.0
     */
    public ?int $uploaderId = null;

    /**
     * @var mixed The filename(s) that the resulting assets must have.
     * @used-by filename()
     */
    public mixed $filename = null;

    /**
     * @var mixed The file kind(s) that the resulting assets must be.
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
     *   .kind('image')
     *   .all() %}
     * ```
     * @used-by kind()
     */
    public mixed $kind = null;

    /**
     * @var bool|null Whether the query should filter assets depending on whether they have alternative text.
     * @used-by hasAlt()
     * @since 4.0.0
     */
    public ?bool $hasAlt = null;

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
     *   .kind('image')
     *   .width('>= 500')
     *   .all() %}
     * ```
     * @used-by width()
     */
    public mixed $width = null;

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
     *   .kind('image')
     *   .height('>= 500')
     *   .all() %}
     * ```
     * @used-by height()
     */
    public mixed $height = null;

    /**
     * @var mixed The size (in bytes) that the resulting assets must have.
     * @used-by size()
     */
    public mixed $size = null;

    /**
     * @var mixed The Date Modified that the resulting assets must have.
     * @used-by dateModified()
     */
    public mixed $dateModified = null;

    /**
     * @var bool Whether the query should search the subfolders of [[folderId]].
     * @used-by includeSubfolders()
     */
    public bool $includeSubfolders = false;

    /**
     * @var string|null The folder path that resulting assets must live within
     * @used-by folderPath()
     * @since 3.7.39
     */
    public ?string $folderPath = null;

    /**
     * @var mixed The asset transform indexes that should be eager-loaded, if they exist
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
     *   .kind('image')
     *   .withTransforms(['thumb'])
     *   .all() %}
     * ```
     * @used-by withTransforms()
     */
    public mixed $withTransforms = null;

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
     * Sets the [[$editable]] property.
     *
     * @param bool|null $value The property value (defaults to true)
     * @return self self reference
     * @uses $editable
     * @since 4.4.0
     */
    public function editable(?bool $value = true): self
    {
        $this->editable = $value;
        return $this;
    }

    /**
     * Sets the [[$savable]] property.
     *
     * @param bool|null $value The property value (defaults to true)
     * @return self self reference
     * @uses $savable
     * @since 4.4.0
     */
    public function savable(?bool $value = true): self
    {
        $this->savable = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the volume the assets belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `'foo'` | in a volume with a handle of `foo`.
     * | `'not foo'` | not in a volume with a handle of `foo`.
     * | `['foo', 'bar']` | in a volume with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a volume with a handle of `foo` or `bar`.
     * | a [[Volume]] object | in a volume represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets in the Foo volume #}
     * {% set {elements-var} = {twig-method}
     *   .volume('foo')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets in the Foo group
     * ${elements-var} = {php-method}
     *     ->volume('foo')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $volumeId
     */
    public function volume(mixed $value): self
    {
        if (Db::normalizeParam($value, function($item) {
            if (is_string($item)) {
                $item = Craft::$app->getVolumes()->getVolumeByHandle($item);
            }
            return $item instanceof Volume ? $item->id : null;
        })) {
            $this->volumeId = $value;
        } elseif ($value !== null) {
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
     * Narrows the query results based on the volumes the assets belong to, per the volumes’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `1` | in a volume with an ID of 1.
     * | `'not 1'` | not in a volume with an ID of 1.
     * | `[1, 2]` | in a volume with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a volume with an ID of 1 or 2.
     * | `':empty:'` | that haven’t been stored in a volume yet
     *
     * ---
     *
     * ```twig
     * {# Fetch assets in the volume with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .volumeId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets in the volume with an ID of 1
     * ${elements-var} = {php-method}
     *     ->volumeId(1)
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $volumeId
     */
    public function volumeId(mixed $value): self
    {
        $this->volumeId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the folders the assets belong to, per the folders’ IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `1` | in a folder with an ID of 1.
     * | `'not 1'` | not in a folder with an ID of 1.
     * | `[1, 2]` | in a folder with an ID of 1 or 2.
     * | `['not', 1, 2]` | not in a folder with an ID of 1 or 2.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets in the folder with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .folderId(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets in the folder with an ID of 1
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
     * @param mixed $value The property value
     * @return self self reference
     * @uses $folderId
     */
    public function folderId(mixed $value): self
    {
        $this->folderId = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the user the assets were uploaded by, per the user’s IDs.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `1` | uploaded by the user with an ID of 1.
     * | a [[User]] object | uploaded by the user represented by the object.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets uploaded by the user with an ID of 1 #}
     * {% set {elements-var} = {twig-method}
     *   .uploader(1)
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets uploaded by the user with an ID of 1
     * ${elements-var} = {php-method}
     *     ->uploader(1)
     *     ->all();
     * ```
     *
     * @param int|User|null $value The property value
     * @return self self reference
     * @uses $uploaderId
     * @since 3.4.0
     */
    public function uploader(int|User|null $value): self
    {
        if ($value instanceof User) {
            $this->uploaderId = $value->id;
        } elseif (is_numeric($value)) {
            $this->uploaderId = $value;
        } else {
            throw new InvalidArgumentException('Invalid uploader value');
        }
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ filenames.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `'foo.jpg'` | with a filename of `foo.jpg`.
     * | `'foo*'` | with a filename that begins with `foo`.
     * | `'*.jpg'` | with a filename that ends with `.jpg`.
     * | `'*foo*'` | with a filename that contains `foo`.
     * | `'not *foo*'` | with a filename that doesn’t contain `foo`.
     * | `['*foo*', '*bar*']` | with a filename that contains `foo` or `bar`.
     * | `['not', '*foo*', '*bar*']` | with a filename that doesn’t contain `foo` or `bar`.
     *
     * ---
     *
     * ```twig
     * {# Fetch all the hi-res images #}
     * {% set {elements-var} = {twig-method}
     *   .filename('*@2x*')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the hi-res images
     * ${elements-var} = {php-method}
     *     ->filename('*@2x*')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $filename
     */
    public function filename(mixed $value): self
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
     * | Value | Fetches assets…
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
     *   .kind('image')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the images
     * ${elements-var} = {php-method}
     *     ->kind('image')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $kind
     */
    public function kind(mixed $value): self
    {
        $this->kind = $value;
        return $this;
    }

    /**
     * Narrows the query results based on whether the assets have alternative text.
     *
     * @param bool|null $value The property value
     * @return self self reference
     * @uses $hasAlt
     */
    public function hasAlt(?bool $value = true): self
    {
        $this->hasAlt = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ image widths.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
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
     *   .kind('image')
     *   .width('>= 1000')
     *   .all() %}
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
     * @return self self reference
     * @uses $width
     */
    public function width(mixed $value): self
    {
        $this->width = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ image heights.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
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
     *   .kind('image')
     *   .height('>= 1000')
     *   .all() %}
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
     * @return self self reference
     * @uses $height
     */
    public function height(mixed $value): self
    {
        $this->height = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ file sizes (in bytes).
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
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
     *   .size('< 1000')
     *   .all() %}
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
     * @return self self reference
     * @uses $size
     */
    public function size(mixed $value): self
    {
        $this->size = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the assets’ files’ last-modified dates.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `'>= 2018-04-01'` | that were modified on or after 2018-04-01.
     * | `'< 2018-05-01'` | that were modified before 2018-05-01.
     * | `['and', '>= 2018-04-04', '< 2018-05-01']` | that were modified between 2018-04-01 and 2018-05-01.
     * | `now`/`today`/`tomorrow`/`yesterday` | that were modified at midnight of the specified relative date.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets modified in the last month #}
     * {% set start = date('30 days ago')|atom %}
     *
     * {% set {elements-var} = {twig-method}
     *   .dateModified(">= #{start}")
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets modified in the last month
     * $start = (new \DateTime('30 days ago'))->format(\DateTime::ATOM);
     *
     * ${elements-var} = {php-method}
     *     ->dateModified(">= {$start}")
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $dateModified
     */
    public function dateModified(mixed $value): self
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
     * {# Fetch assets in the folder with an ID of 1 (including its subfolders) #}
     * {% set {elements-var} = {twig-method}
     *   .folderId(1)
     *   .includeSubfolders()
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets in the folder with an ID of 1 (including its subfolders)
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
     * @return self self reference
     * @uses $includeSubfolders
     */
    public function includeSubfolders(bool $value = true): self
    {
        $this->includeSubfolders = $value;
        return $this;
    }

    /**
     * Narrows the query results based on the folders the assets belong to, per the folders’ paths.
     *
     * Possible values include:
     *
     * | Value | Fetches assets…
     * | - | -
     * | `foo/` | in a `foo/` folder (excluding nested folders).
     * | `foo/*` | in a `foo/` folder (including nested folders).
     * | `'not foo/*'` | not in a `foo/` folder (including nested folders).
     * | `['foo/*', 'bar/*']` | in a `foo/` or `bar/` folder (including nested folders).
     * | `['not', 'foo/*', 'bar/*']` | not in a `foo/` or `bar/` folder (including nested folders).
     *
     * ---
     *
     * ```twig
     * {# Fetch assets in the foo/ folder or its nested folders #}
     * {% set {elements-var} = {twig-method}
     *   .folderPath('foo/*')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets in the foo/ folder or its nested folders
     * ${elements-var} = {php-method}
     *     ->folderPath('foo/*')
     *     ->all();
     * ```
     *
     * @param mixed $value The property value
     * @return self self reference
     * @uses $folderPath
     * @since 3.7.39
     */
    public function folderPath(mixed $value): self
    {
        $this->folderPath = $value;
        return $this;
    }

    /**
     * Causes the query to return matching assets eager-loaded with image transform indexes.
     *
     * This can improve performance when displaying several image transforms at once, if the transforms
     * have already been generated.
     *
     * Transforms can be specified as their handle or an object that contains `width` and/or `height` properties.
     *
     * You can include `srcset`-style sizes (e.g. `100w` or `2x`) following a normal transform definition, for example:
     *
     * ::: code
     *
     * ```twig
     * [{width: 1000, height: 600}, '1.5x', '2x', '3x']
     * ```
     *
     * ```php
     * [['width' => 1000, 'height' => 600], '1.5x', '2x', '3x']
     * ```
     *
     * :::
     *
     * When a `srcset`-style size is encountered, the preceding normal transform definition will be used as a
     * reference when determining the resulting transform dimensions.
     *
     * ---
     *
     * ```twig
     * {# Fetch assets with the 'thumbnail' and 'hiResThumbnail' transform data preloaded #}
     * {% set {elements-var} = {twig-method}
     *   .kind('image')
     *   .withTransforms(['thumbnail', 'hiResThumbnail'])
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch assets with the 'thumbnail' and 'hiResThumbnail' transform data preloaded
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
    public function withTransforms(string|array|null $value = null): self
    {
        $this->withTransforms = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function afterPopulate(array $elements): array
    {
        /** @var Asset[] $elements */
        $elements = parent::afterPopulate($elements);

        // Eager-load transforms?
        if ($this->withTransforms && !$this->asArray) {
            $transforms = $this->withTransforms;
            if (!is_array($transforms)) {
                $transforms = is_string($transforms) ? StringHelper::split($transforms) : [$transforms];
            }

            Craft::$app->getImageTransforms()->eagerLoadTransforms($elements, $transforms);
        }

        return $elements;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->_normalizeVolumeId();

        // See if 'volume' was set to an invalid handle
        if ($this->volumeId === []) {
            return false;
        }

        $this->joinElementTable(Table::ASSETS);
        $this->subQuery->innerJoin(['volumeFolders' => Table::VOLUMEFOLDERS], '[[volumeFolders.id]] = [[assets.folderId]]');
        $this->query->innerJoin(['volumeFolders' => Table::VOLUMEFOLDERS], '[[volumeFolders.id]] = [[assets.folderId]]');

        $this->query->select([
            'assets.volumeId',
            'assets.folderId',
            'assets.uploaderId',
            'assets.filename',
            'assets.kind',
            'assets.width',
            'assets.height',
            'assets.size',
            'assets.focalPoint',
            'assets.keptFile',
            'assets.dateModified',
            'volumeFolders.path AS folderPath',
        ]);

        // todo: cleanup after next breakpoint
        if (Craft::$app->getDb()->columnExists(Table::ASSETS, 'alt')) {
            $this->query->addSelect(['assets.alt']);
        }

        if ($this->volumeId) {
            if ($this->volumeId === ':empty:') {
                $this->subQuery->andWhere(['assets.volumeId' => null]);
            } else {
                $this->subQuery->andWhere(['assets.volumeId' => $this->volumeId]);
            }
        }

        if ($this->folderId) {
            $folderCondition = Db::parseNumericParam('assets.folderId', $this->folderId);
            if (is_numeric($this->folderId) && $this->includeSubfolders) {
                $assetsService = Craft::$app->getAssets();
                $descendants = $assetsService->getAllDescendantFolders($assetsService->getFolderById($this->folderId));
                $folderCondition = ['or', $folderCondition, ['in', 'assets.folderId', array_keys($descendants)]];
            }
            $this->subQuery->andWhere($folderCondition);
        }

        if ($this->folderPath) {
            $this->subQuery->andWhere(Db::parseParam('volumeFolders.path', $this->folderPath));
        }

        if ($this->uploaderId) {
            $this->subQuery->andWhere(['uploaderId' => $this->uploaderId]);
        }

        if ($this->filename) {
            $this->subQuery->andWhere(Db::parseParam('assets.filename', $this->filename));
        }

        if ($this->kind) {
            $kindCondition = ['or', Db::parseParam('assets.kind', $this->kind)];
            $kinds = Assets::getFileKinds();
            foreach ((array)$this->kind as $kind) {
                if (isset($kinds[$kind])) {
                    foreach ($kinds[$kind]['extensions'] as $extension) {
                        $kindCondition[] = ['like', 'assets.filename', "%.$extension", false];
                    }
                }
            }
            $this->subQuery->andWhere($kindCondition);
        }

        if ($this->hasAlt !== null) {
            $this->subQuery->andWhere($this->hasAlt ? ['not', ['assets.alt' => null]] : ['assets.alt' => null]);
        }

        if ($this->width) {
            $this->subQuery->andWhere(Db::parseNumericParam('assets.width', $this->width));
        }

        if ($this->height) {
            $this->subQuery->andWhere(Db::parseNumericParam('assets.height', $this->height));
        }

        if ($this->size) {
            $this->subQuery->andWhere(Db::parseNumericParam('assets.size', $this->size, '=', Schema::TYPE_BIGINT));
        }

        if ($this->dateModified) {
            $this->subQuery->andWhere(Db::parseDateParam('assets.dateModified', $this->dateModified));
        }

        $this->_applyAuthParam($this->editable, 'viewAssets', 'viewPeerAssets');
        $this->_applyAuthParam($this->savable, 'saveAssets', 'savePeerAssets');

        return parent::beforePrepare();
    }

    /**
     * @param bool|null $value
     * @param string $permissionPrefix
     * @param string $peerPermissionPrefix
     * @throws QueryAbortedException
     */
    private function _applyAuthParam(?bool $value, string $permissionPrefix, string $peerPermissionPrefix): void
    {
        if ($value === null) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        $fullyAuthorizedVolumeIds = [];
        $partiallyAuthorizedVolumeIds = [];
        $unauthorizedVolumeIds = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            if ($user->can("$peerPermissionPrefix:$volume->uid")) {
                $fullyAuthorizedVolumeIds[] = $volume->id;
            } elseif ($user->can("$permissionPrefix:$volume->uid")) {
                $partiallyAuthorizedVolumeIds[] = $volume->id;
            } else {
                $unauthorizedVolumeIds[] = $volume->id;
            }
        }

        if ($value) {
            if (!$fullyAuthorizedVolumeIds && !$partiallyAuthorizedVolumeIds) {
                throw new QueryAbortedException();
            }

            $this->subQuery->andWhere(array_filter([
                'or',
                $fullyAuthorizedVolumeIds
                    ? ['assets.volumeId' => $fullyAuthorizedVolumeIds]
                    : null,
                $partiallyAuthorizedVolumeIds
                    ? [
                        'assets.volumeId' => $partiallyAuthorizedVolumeIds,
                        'assets.uploaderId' => $user->id,
                    ]
                    : null,
            ]));
        } else {
            if (!$unauthorizedVolumeIds && !$partiallyAuthorizedVolumeIds) {
                throw new QueryAbortedException();
            }

            $this->subQuery->andWhere(array_filter([
                'or',
                $unauthorizedVolumeIds
                    ? ['assets.volumeId' => $unauthorizedVolumeIds]
                    : null,
                $partiallyAuthorizedVolumeIds
                    ? [
                        'and',
                        ['assets.volumeId' => $partiallyAuthorizedVolumeIds],
                        [
                            'or',
                            ['not', ['assets.uploaderId' => $user->id]],
                            ['assets.uploaderId' => null],
                        ],
                    ]
                    : null,
            ]));
        }
    }

    /**
     * Normalizes the volumeId param to an array of IDs or null
     */
    private function _normalizeVolumeId(): void
    {
        if ($this->volumeId === ':empty:') {
            return;
        }

        if (empty($this->volumeId)) {
            $this->volumeId = is_array($this->volumeId) ? [] : null;
        } elseif (is_numeric($this->volumeId)) {
            $this->volumeId = [$this->volumeId];
        } elseif (!is_array($this->volumeId) || !ArrayHelper::isNumeric($this->volumeId)) {
            $this->volumeId = (new Query())
                ->select(['id'])
                ->from([Table::VOLUMES])
                ->where(Db::parseNumericParam('id', $this->volumeId))
                ->column();
        }
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    protected function cacheTags(): array
    {
        $tags = [];
        if ($this->volumeId && $this->volumeId !== ':empty:') {
            foreach ($this->volumeId as $volumeId) {
                $tags[] = "volume:$volumeId";
            }
        }
        return $tags;
    }
}
