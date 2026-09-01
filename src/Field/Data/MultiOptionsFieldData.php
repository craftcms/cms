<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use ArrayObject;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;

/** @extends ArrayObject<int, OptionData> */
class MultiOptionsFieldData extends ArrayObject
{
    /** @var list<OptionData> */
    private array $_options = [];

    /**
     * @return list<OptionData>
     */
    #[AllowedInSandbox]
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @param  list<OptionData>  $options
     */
    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }

    #[AllowedInSandbox]
    public function contains(mixed $value): bool
    {
        $value = (string) $value;

        foreach ($this as $selectedValue) {
            if ($value === $selectedValue->value) {
                return true;
            }
        }

        return false;
    }
}
