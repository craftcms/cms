<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Queries\Concerns\Asset;

use Craft;
use Illuminate\Support\Collection;

/**
 * @internal
 */
trait EagerloadsTransforms
{
    /**
     * @var mixed The asset transform indexes that should be eager-loaded, if they exist
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

            // Eager-load transforms?
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

            Craft::$app->getImageTransforms()->eagerLoadTransforms($result->all(), $transforms);

            return $result;
        });
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
     * @uses $withTransforms
     */
    public function withTransforms(string|array|null $value = null): static
    {
        $this->withTransforms = $value;

        return $this;
    }
}
