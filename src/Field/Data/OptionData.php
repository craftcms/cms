<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use Stringable;

class OptionData implements Serializable, Stringable
{
    public function __construct(
        #[AllowedInSandbox]
        public ?string $label,
        #[AllowedInSandbox]
        public ?string $value,
        #[AllowedInSandbox]
        public bool $selected,
        #[AllowedInSandbox]
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
