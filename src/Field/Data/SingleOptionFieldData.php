<?php

namespace CraftCms\Cms\Field\Data;

/**
 * Single-select option field data class.
 *
 * @since 6.0.0
 */
final class SingleOptionFieldData extends OptionData
{
    /** @var OptionData[] */
    private array $_options = [];

    /**
     * Returns the options.
     *
     * @return OptionData[]
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets the options.
     *
     * @param  OptionData[]  $options
     */
    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }
}
