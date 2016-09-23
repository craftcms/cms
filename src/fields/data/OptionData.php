<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields\data;

use craft\app\base\Savable;

/**
 * Class OptionData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class OptionData implements Savable
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $value;

    /**
     * @var
     */
    public $selected;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string $label
     * @param string $value
     * @param        $selected
     *
     * @return OptionData
     */
    public function __construct($label, $value, $selected)
    {
        $this->label = $label;
        $this->value = $value;
        $this->selected = $selected;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * @inheritdoc
     */
    public function getSavableValue()
    {
        return $this->value;
    }
}
