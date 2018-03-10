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
 * @since 3.0
 */
class SingleOptionFieldData extends OptionData
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private $_options = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
    }
}
