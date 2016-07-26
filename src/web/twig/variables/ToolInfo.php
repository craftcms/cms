<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\base\ToolInterface;

/**
 * ToolInfo represents a tool class, making information about it available to the templates.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ToolInfo extends ComponentInfo
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the tool's icon value.
     *
     * @return string
     */
    public function getIconValue()
    {
        /** @var ToolInterface $component */
        $component = $this->component;

        return $component::iconValue();
    }

    /**
     * Returns the tool's options HTML.
     *
     * @return string
     */
    public function getOptionsHtml()
    {
        /** @var ToolInterface $component */
        $component = $this->component;

        return $component::optionsHtml();
    }

    /**
     * Returns the tool's button label.
     *
     * @return string
     */
    public function getButtonLabel()
    {
        /** @var ToolInterface $component */
        $component = $this->component;

        return $component::buttonLabel();
    }
}
