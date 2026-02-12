<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use craft\base\Serializable;
use craft\web\twig\AllowedInSandbox;
use Stringable;

#[AllowedInSandbox]
final class IconData implements Serializable, Stringable
{
    /**
     * Constructor
     *
     * @param  string  $name  The icon name
     * @param  string[]  $styles  The Font Awesome styles the icon is available in
     */
    public function __construct(
        public string $name,
        public array $styles,
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }

    public function serialize(): string
    {
        return $this->name;
    }
}
