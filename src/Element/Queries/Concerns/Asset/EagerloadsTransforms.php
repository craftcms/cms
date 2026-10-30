<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Concerns\Asset;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Element\Enums\ElementQueryParamType;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use Illuminate\Support\Collection;
use Override;

/**
 * @internal
 */
trait EagerloadsTransforms
{
    /**
     * @var mixed The asset transforms that should be preloaded, if supported by their drivers
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

    /**
     * Preloads the requested transforms. The element eager loader calls this too.
     */
    #[Override]
    public function afterHydrate(Collection $elements): Collection
    {
        $elements = parent::afterHydrate($elements);

        if (! $this->withTransforms) {
            return $elements;
        }

        $transforms = $this->withTransforms;
        if (! is_array($transforms)) {
            $transforms = is_string($transforms)
                ? str($transforms)->explode(',')->all()
                : [$transforms];
        }

        app(AssetTransformers::class)->preload($elements->all(), $transforms);

        return $elements;
    }

    /**
     * Asks capable asset transform drivers to preload the requested transforms for matching assets.
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
    #[ElementQueryParam(ElementQueryParamType::StringList, 'A list of transform handles to preload.')]
    public function withTransforms(string|array|null $value = null): static
    {
        $this->withTransforms = $value;

        return $this;
    }
}
