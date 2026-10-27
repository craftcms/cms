<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis;

/**
 * Changes that image analysis tasks make to an asset, saved together once every task has applied its results.
 *
 * @since 6.0.0
 */
class ImageAnalysisChanges
{
    /** @var array<string, mixed> Attributes that apply to the asset in every site, such as its focal point. */
    public private(set) array $attributes = [];

    /** @var array<int, array<string, mixed>> Attributes that apply to the asset in one site, such as its alt text, indexed by site ID. */
    public private(set) array $siteAttributes = [];

    /**
     * Sets an attribute that applies to the asset in every site.
     */
    public function set(string $attribute, mixed $value): void
    {
        $this->attributes[$attribute] = $value;
    }

    /**
     * Returns an attribute that a task has set for every site, or a default if none has, so tasks that change the same
     * attribute, such as the asset’s analysis, can build on each other’s changes.
     */
    public function get(string $attribute, mixed $default = null): mixed
    {
        return array_key_exists($attribute, $this->attributes) ? $this->attributes[$attribute] : $default;
    }

    /**
     * Sets an attribute for the asset in one site.
     */
    public function setForSite(int $siteId, string $attribute, mixed $value): void
    {
        $this->siteAttributes[$siteId][$attribute] = $value;
    }

    public function isEmpty(): bool
    {
        return $this->attributes === [] && $this->siteAttributes === [];
    }
}
