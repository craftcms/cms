<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Field\Data;

use craft\base\Serializable;

/**
 * Class OptionData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 */
class OptionData implements Serializable
{
    public ?string $label = null;

    public ?string $value = null;

    public bool $selected;

    /**
     * @since 3.5.10
     */
    public bool $valid;

    /**
     * @since 5.8.0
     */
    public ?string $icon = null;

    /**
     * @since 5.8.0
     */
    public ?string $color = null;

    /**
     * Constructor
     */
    public function __construct(
        ?string $label,
        ?string $value,
        bool $selected,
        bool $valid = true,
        ?string $icon = null,
        ?string $color = null,
    ) {
        $this->label = $label;
        $this->value = $value;
        $this->selected = $selected;
        $this->valid = $valid;
        $this->icon = $icon;
        $this->color = $color;

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
    public function serialize(): mixed
    {
        return $this->value;
    }
}
