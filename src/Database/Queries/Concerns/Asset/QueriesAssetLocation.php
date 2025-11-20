<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\Asset;

use Craft;
use craft\models\Volume;
use CraftCms\Cms\Database\Queries\AssetQuery;
use CraftCms\Cms\Database\Queries\Exceptions\QueryAbortedException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * @internal
 */
trait QueriesAssetLocation
{
    /**
     * @var mixed The volume ID(s) that the resulting assets must be in.
     *            ---
     *            ```php
     *            // fetch assets in the Logos volume
     *            $logos = \craft\elements\Asset::find()
     *            ->volume('logos')
     *            ->all();
     *            ```
     *            ```twig
     *            {# fetch assets in the Logos volume #}
     *            {% set logos = craft.assets()
     *            .volume('logos')
     *            .all() %}
     *            ```
     *
     * @used-by volume()
     * @used-by volumeId()
     */
    public mixed $volumeId = null;

    /**
     * @var bool Whether the query should search the subfolders of [[folderId]].
     *
     * @used-by includeSubfolders()
     */
    public bool $includeSubfolders = false;

    /**
     * @var mixed The folder path that resulting assets must live within
     *
     * @used-by folderPath()
     */
    public mixed $folderPath = null;

    /**
     * @var mixed The asset folder ID(s) that the resulting assets must be in.
     *
     * @used-by folderId()
     */
    public mixed $folderId = null;

    protected function initQueriesAssetLocation(): void
    {
        $this->beforeQuery(function (AssetQuery $assetQuery) {
            $this->normalizeVolumeId();

            // See if 'volume' was set to an invalid handle
            if ($this->volumeId === []) {
                throw new QueryAbortedException;
            }

            $assetQuery->subQuery->join(new Alias(Table::VOLUMEFOLDERS, 'volumeFolders'), 'volumeFolders.id', '=', 'assets.folderId');
            $assetQuery->query->join(new Alias(Table::VOLUMEFOLDERS, 'volumeFolders'), 'volumeFolders.id', '=', 'assets.folderId');

            if ($assetQuery->volumeId) {
                if ($assetQuery->volumeId === ':empty:') {
                    $assetQuery->subQuery->whereNull('assets.volumeId');
                } else {
                    $assetQuery->subQuery->whereIn('assets.volumeId', Arr::wrap($this->volumeId));
                }
            }

            if ($assetQuery->folderId) {
                // [X] => X, so includeSubfolders works with GraphQL
                // (see https://github.com/craftcms/cms/issues/17023)
                if (is_array($assetQuery->folderId) && count($assetQuery->folderId) === 1 && Arr::isNumeric($assetQuery->folderId)) {
                    $assetQuery->folderId = reset($assetQuery->folderId);
                }

                $assetQuery->subQuery->where(function (Builder $query) use ($assetQuery) {
                    $query->whereNumericParam('assets.folderId', $assetQuery->folderId)
                        ->when(
                            is_numeric($assetQuery->folderId) && $assetQuery->includeSubfolders,
                            function (Builder $query) use ($assetQuery) {
                                $assetsService = Craft::$app->getAssets();
                                $descendants = $assetsService->getAllDescendantFolders($assetsService->getFolderById($assetQuery->folderId));

                                $query->orWhereIn('assets.folderId', array_keys($descendants));
                            }
                        );
                });
            }

            if ($assetQuery->folderPath) {
                $folderPath = (array) $assetQuery->folderPath;
                foreach ($folderPath as &$path) {
                    if (
                        is_string($path) &&
                        ! str_ends_with($path, '/') &&
                        Query::escapeParam($path) === $path
                    ) {
                        $path .= '/';
                    }
                }

                $assetQuery->subQuery->whereParam('volumeFolders.path', $folderPath);
            }
        });
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
     * @param  mixed  $value  The property value
     * @return static self reference
     *
     * @uses $volumeId
     */
    public function volume(mixed $value): static
    {
        if (Query::normalizeParam($value, function ($item) {
            if (is_string($item)) {
                $item = Craft::$app->getVolumes()->getVolumeByHandle($item);
            }

            return $item instanceof Volume ? $item->id : null;
        })) {
            $this->volumeId = $value;
        } elseif ($value !== null) {
            $this->volumeId = DB::table(Table::VOLUMES)
                ->whereParam('handle', $value)
                ->whereNull('dateDeleted')
                ->pluck('id')
                ->all();
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
     * @param  mixed  $value  The property value
     * @return static self reference
     *
     * @uses $volumeId
     */
    public function volumeId(mixed $value): static
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
     * @param  mixed  $value  The property value
     * @return static self reference
     *
     * @uses $folderId
     */
    public function folderId(mixed $value): static
    {
        $this->folderId = $value;

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
     * @param  bool  $value  The property value (defaults to true)
     * @return static self reference
     *
     * @uses $includeSubfolders
     */
    public function includeSubfolders(bool $value = true): static
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
     * @param  mixed  $value  The property value
     * @return static self reference
     *
     * @uses $folderPath
     */
    public function folderPath(mixed $value): static
    {
        $this->folderPath = $value;

        return $this;
    }

    /**
     * Normalizes the volumeId param to an array of IDs or null
     */
    private function normalizeVolumeId(): void
    {
        if ($this->volumeId === ':empty:') {
            return;
        }

        if (empty($this->volumeId)) {
            $this->volumeId = is_array($this->volumeId) ? [] : null;

            return;
        }
        if (is_numeric($this->volumeId)) {
            $this->volumeId = [$this->volumeId];

            return;
        }

        if (! is_array($this->volumeId) || ! Arr::isNumeric($this->volumeId)) {
            $this->volumeId = DB::table(Table::VOLUMES)
                ->whereNumericParam('id', $this->volumeId)
                ->pluck('id')
                ->all();
        }
    }
}
