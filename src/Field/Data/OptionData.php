<?php

namespace CraftCms\Cms\Field\Data;

use craft\base\Serializable;

/**
 * Class OptionData
 *
 * @since 6.0.0
 */
class OptionData implements Serializable
{
    public function __construct(
        public ?string $label = null,
        public ?string $value = null,
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

    /**
     * {@inheritdoc}
     */
    public function serialize(): ?string
    {
        return $this->value;
    }
}
