<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\Asset;

use CraftCms\Cms\Database\Queries\AssetQuery;
use CraftCms\Cms\Support\Query;

/**
 * @internal
 */
trait QueriesSizes
{
    /**
     * @var mixed The width (in pixels) that the resulting assets must have.
     *            ---
     *            ```php{4}
     *            // fetch images that are at least 500 pixels wide
     *            $images = \craft\elements\Asset::find()
     *            ->kind('image')
     *            ->width('>= 500')
     *            ->all();
     *            ```
     *            ```twig{4}
     *            {# fetch images that are at least 500 pixes wide #}
     *            {% set logos = assets()
     *            .kind('image')
     *            .width('>= 500')
     *            .all() %}
     *            ```
     *
     * @used-by width()
     */
    public mixed $width = null;

    /**
     * @var mixed The height (in pixels) that the resulting assets must have.
     *            ---
     *            ```php{4}
     *            // fetch images that are at least 500 pixels high
     *            $images = \craft\elements\Asset::find()
     *            ->kind('image')
     *            ->height('>= 500')
     *            ->all();
     *            ```
     *            ```twig{4}
     *            {# fetch images that are at least 500 pixes high #}
     *            {% set logos = assets()
     *            .kind('image')
     *            .height('>= 500')
     *            .all() %}
     *            ```
     *
     * @used-by height()
     */
    public mixed $height = null;

    /**
     * @var mixed The size (in bytes) that the resulting assets must have.
     *
     * @used-by size()
     */
    public mixed $size = null;

    protected function initQueriesSizes(): void
    {
        $this->beforeQuery(function (AssetQuery $assetQuery) {
            if ($assetQuery->width) {
                $assetQuery->subQuery->whereNumericParam('assets.width', $assetQuery->width);
            }

            if ($assetQuery->height) {
                $assetQuery->subQuery->whereNumericParam('assets.height', $assetQuery->height);
            }

            if ($assetQuery->size) {
                $assetQuery->subQuery->whereNumericParam('assets.size', $assetQuery->size, '=', Query::TYPE_BIGINT);
            }
        });
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
     * @uses $width
     */
    public function width(mixed $value): static
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
     * @uses $height
     */
    public function height(mixed $value): static
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
     * @uses $size
     */
    public function size(mixed $value): static
    {
        $this->size = $value;

        return $this;
    }
}
