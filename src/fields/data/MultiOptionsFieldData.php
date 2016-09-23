<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields\data;

use craft\app\base\Savable;
use craft\app\helpers\Json;

/**
 * Multi-select option field data class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MultiOptionsFieldData extends \ArrayObject implements Savable
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
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    /**
     * @param mixed $value
     *
     * @return boolean
     */
    public function contains($value)
    {
        $value = (string)$value;

        foreach ($this as $selectedValue) {
            if ($value == $selectedValue->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getSavableValue()
    {
        return Json::encode($this->getArrayCopy());
    }
}
