<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\web\twig;

use craft\app\web\twig\nodes\RegisterResourceNode;

/**
 * Class Parser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Parser extends \Twig_Parser
{
    // Protected Methods
    // =========================================================================

    protected function filterBodyNodes(\Twig_NodeInterface $node)
    {
        // Bypass "include" nodes as they "capture" the output
        if ($node instanceof RegisterResourceNode) {
            return $node;
        }

        return parent::filterBodyNodes($node);
    }
}
