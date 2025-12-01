<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use ArrayObject;

/**
 * Multi-select option field data class.
 */
final class MultiOptionsFieldData extends ArrayObject
{
    /** @var OptionData[] */
    private array $_options = [];

    /**
     * @return OptionData[]
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @param  OptionData[]  $options
     */
    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }

    public function contains(mixed $value): bool
    {
        $value = (string) $value;

        foreach ($this as $selectedValue) {
            /** @var OptionData $selectedValue */
            if ($value === $selectedValue->value) {
                return true;
            }
        }

        return false;
    }
}
