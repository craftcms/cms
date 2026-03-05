<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use craft\base\Serializable;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Stringable;

#[AllowedInSandbox]
class OptionData implements Serializable, Stringable
{
    public function __construct(
        public ?string $label,
        public ?string $value,
        public bool $selected,
        public bool $valid = true,
        public ?string $icon = null,
        public ?string $color = null,
    ) {
        if ($this->icon === '') {
            $this->icon = null;
        }

        if ($this->color === '') {
            $this->color = null;
        }
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function serialize(): ?string
    {
        return $this->value;
    }
}
