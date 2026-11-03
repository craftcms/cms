<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Stringable;

/**
 * @since 6.0.0
 */
class IconData implements Serializable, Stringable
{
    /**
     * @var array<string, array{name: string, terms: string, pro: bool, styles: list<string>}> Info about the Font Awesome icons
     *
     * @see getKeywords()
     */
    private static array $fontAwesomeIcons;

    /**
     * Constructor
     *
     * @param  string  $name  The icon name
     * @param  string[]  $styles  The Font Awesome styles the icon is available in
     * @param  string|null  $set  The handle of the custom icon set the icon belongs to, or `null` for Font Awesome icons
     */
    public function __construct(
        #[AllowedInSandbox]
        public string $name,
        #[AllowedInSandbox]
        public array $styles,
        #[AllowedInSandbox]
        public ?string $set = null,
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }

    public function serialize(): string
    {
        return $this->set !== null ? "$this->set:$this->name" : $this->name;
    }

    /**
     * Returns the icon’s SVG markup.
     *
     * @return string|null The SVG markup, or `null` if the icon doesn’t exist
     *
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getSvg(): ?string
    {
        return Icons::svg($this->serialize()) ?: null;
    }

    /**
     * Returns the icon’s search keywords.
     *
     * @return string[]
     *
     * @since 5.12.0
     */
    #[AllowedInSandbox]
    public function getKeywords(): array
    {
        if ($this->set !== null) {
            return app(IconSets::class)->getIconSetByHandle($this->set)?->getKeywords($this->name) ?? [];
        }

        if (! isset(self::$fontAwesomeIcons)) {
            self::$fontAwesomeIcons = require CmsAssets::resourcesPath('icons/index.php');
        }

        $terms = self::$fontAwesomeIcons[$this->name]['terms'] ?? '';

        return array_values(array_filter(explode(' ', $terms), fn (string $term) => $term !== ''));
    }
}
