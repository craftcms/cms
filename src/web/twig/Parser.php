<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig;

use craft\web\twig\nodes\RegisterResourceNode;

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

    /**
     * @param \Twig_NodeInterface $node
     *
     * @return \Twig_NodeInterface|null
     */
    protected function filterBodyNodes(
        /** @noinspection PhpDeprecationInspection */
        \Twig_NodeInterface $node
    ) {
        // Bypass "include" nodes as they "capture" the output
        if ($node instanceof RegisterResourceNode) {
            return $node;
        }

        return parent::filterBodyNodes($node);
    }
}
