<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields\data;

/**
 * Single-select option field data class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SingleOptionFieldData extends OptionData
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_options;

    // Public Methods
    // =========================================================================

    /**
     * Returns the options.
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets the options.
     *
     * @param array $options
     *
     * @return void
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }
}
