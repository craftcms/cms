<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use CraftCms\Cms\Asset\AssetTransforms;
use Illuminate\Support\Collection;

/**
 * @internal
 */
trait EagerloadsTransforms
{
    /**
     * @var mixed The Asset Transforms that should be preloaded, if supported by their drivers
     *            ---
     *            ```php{4}
     *            // fetch images with their 'thumb' transforms preloaded
     *            $images = \CraftCms\Cms\Asset\Elements\Asset::find()
     *            ->kind('image')
     *            ->withTransforms(['thumb'])
     *            ->all();
     *            ```
     *            ```twig{4}
     *            {# fetch images with their 'thumb' transforms preloaded #}
     *            {% set logos = assets()
     *            .kind('image')
     *            .withTransforms(['thumb'])
     *            .all() %}
     *            ```
     *
     * @used-by withTransforms()
     */
    public mixed $withTransforms = null;

    protected function initEagerloadsTransforms(): void
    {
        $this->afterQuery(function (mixed $result) {
            if (! $result instanceof Collection) {
                return $result;
            }

            if (! $this->withTransforms) {
                return $result;
            }

            if ($this->asArray) {
                return $result;
            }

            $transforms = $this->withTransforms;
            if (! is_array($transforms)) {
                $transforms = is_string($transforms)
                    ? str($transforms)->explode(',')->all()
                    : [$transforms];
            }

            app(AssetTransforms::class)->preload($result->all(), $transforms);

            return $result;
        });
    }

    /**
     * Asks capable Asset Transform drivers to preload the requested transforms for matching assets.
     *
     * This may improve later transform rendering performance, but does not guarantee that output has materialized.
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
     * @param  string[]|string|null  $value
     *
     * @uses $withTransforms
     */
    public function withTransforms(string|array|null $value = null): static
    {
        $this->withTransforms = $value;

        return $this;
    }
}
