<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Database\Query\Builder;

/**
 * @internal
 */
trait QueriesAssetProperties
{
    /**
     * @var int|null The user ID that the resulting assets must have been uploaded by.
     *
     * @used-by uploader()
     */
    public ?int $uploaderId = null;

    /**
     * @var mixed The filename(s) that the resulting assets must have.
     *
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
     * $logos = \CraftCms\Cms\Asset\Elements\Asset::find()
     *     ->kind('image')
     *     ->all();
     * ```
     * ```twig
     * {# fetch only images #}
     * {% set logos = assets()
     *   .kind('image')
     *   .all() %}
     * ```
     *
     * @used-by kind()
     */
    public mixed $kind = null;

    /**
     * @var mixed The Date Modified that the resulting assets must have.
     *
     * @used-by dateModified()
     */
    public mixed $dateModified = null;

    protected function initQueriesAssetProperties(): void
    {
        $this->beforeQuery(function (AssetQuery $assetQuery) {
            if ($assetQuery->uploaderId) {
                $assetQuery->whereIn('uploaderId', Arr::wrap($assetQuery->uploaderId));
            }

            if ($assetQuery->filename) {
                $assetQuery->whereParam('assets.filename', $assetQuery->filename);
            }

            if ($assetQuery->kind) {
                $assetQuery->where(function (Builder $query) use ($assetQuery) {
                    $query->whereParam('assets.kind', $assetQuery->kind);

                    $kinds = AssetsHelper::getFileKinds();

                    foreach ((array) $assetQuery->kind as $kind) {
                        if (! isset($kinds[$kind])) {
                            continue;
                        }

                        foreach ($kinds[$kind]['extensions'] as $extension) {
                            $query->orWhereLike('assets.filename', "%.$extension");
                        }
                    }
                });
            }

            if ($assetQuery->dateModified) {
                $assetQuery->whereDateParam('assets.dateModified', $assetQuery->dateModified);
            }
        });
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
     * @uses $uploaderId
     */
    public function uploader(int|User|UserModel|null $value): static
    {
        if ($value instanceof User || $value instanceof UserModel) {
            $this->uploaderId = $value->id;
        } else {
            // the only remaining possibilities are int|null
            // and neither should lead to an exception
            $this->uploaderId = $value;
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
     *
     *   .filename('*@2x*')
     *   .all() %}
     * ```
     *
     * ```php
     * // Fetch all the hi-res images
     * ${elements-var} = {php-method}
     *
     *     ->filename('*@2x*')
     *     ->all();
     * ```
     *
     * @uses $filename
     */
    public function filename(mixed $value): static
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
     * @uses $kind
     */
    public function kind(mixed $value): static
    {
        $this->kind = $value;

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
     * @uses $dateModified
     */
    public function dateModified(mixed $value): static
    {
        $this->dateModified = $value;

        return $this;
    }
}
