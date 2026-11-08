<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Typecast;
use InvalidArgumentException;

/**
 * Settings for the anchor links that the `|anchors` filter and `@anchors` GraphQL directive add to headings.
 *
 * @since 6.0.0
 */
class AnchorsConfig extends BaseConfig
{
    public const string POS_BEFORE = 'before';

    public const string POS_AFTER = 'after';

    /** The class name that should be given to the named anchors, or `null` for no class. */
    public ?string $anchorClass = null;

    /** Where anchor links should be positioned within headings, relative to the heading text (`before` or `after`). */
    public string $anchorLinkPosition = self::POS_AFTER;

    /** The class name that should be given to anchor links, or `null` for no class. */
    public ?string $anchorLinkClass = 'anchor';

    /** The visible text (or HTML) that anchor links should have. */
    public string $anchorLinkText = '#';

    /**
     * The title and `aria-label` text that anchor links should have. `{heading}` is replaced with the heading’s text.
     * The text is translated with the `site` translation category.
     */
    public string $anchorLinkTitleText = 'Direct link to {heading}';

    /**
     * Whether to render an empty `<span>` with the anchor’s `id` before each heading, rather than adding the `id` to the
     * heading itself.
     */
    public bool $useAdditionalTagToAnchorTo = true;

    /** @param array<string, mixed>|string $value */
    public static function fromConfig(array|string $value): self
    {
        $value = is_array($value) ? $value : Json::decode($value);

        if (! is_array($value)) {
            throw new InvalidArgumentException('The anchors configuration must be an array or a JSON object.');
        }

        Typecast::properties(self::class, $value);

        $config = self::create();

        foreach ($value as $property => $propertyValue) {
            if (property_exists($config, $property) && method_exists($config, $property)) {
                $config->{$property}($propertyValue);
            }
        }

        return $config;
    }

    public function anchorClass(?string $value): self
    {
        $this->anchorClass = $value;

        return $this;
    }

    public function anchorLinkPosition(string $value): self
    {
        if (! in_array($value, [self::POS_BEFORE, self::POS_AFTER], true)) {
            throw new InvalidArgumentException("The anchor link position must be `before` or `after`; got [$value].");
        }

        $this->anchorLinkPosition = $value;

        return $this;
    }

    public function anchorLinkClass(?string $value): self
    {
        $this->anchorLinkClass = $value;

        return $this;
    }

    public function anchorLinkText(string $value): self
    {
        $this->anchorLinkText = $value;

        return $this;
    }

    public function anchorLinkTitleText(string $value): self
    {
        $this->anchorLinkTitleText = $value;

        return $this;
    }

    public function useAdditionalTagToAnchorTo(bool $value = true): self
    {
        $this->useAdditionalTagToAnchorTo = $value;

        return $this;
    }
}
