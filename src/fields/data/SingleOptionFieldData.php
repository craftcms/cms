<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

/**
 * Single-select option field data class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SingleOptionFieldData extends OptionData
{
    /**
     * @var OptionData[]
     */
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
     * @param OptionData[] $options
     */
    public function setOptions(array $options): void
    {
        $this->_options = $options;
    }
}
