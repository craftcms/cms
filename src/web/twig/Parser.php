<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
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
