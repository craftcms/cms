<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class RedirectNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RedirectNode extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('\Craft::$app->getResponse()->redirect(\craft\app\helpers\Url::getUrl(')
            ->subcompile($this->getNode('path'))
            ->raw("), ")
            ->subcompile($this->getNode('httpStatusCode'))
            ->raw(");\n")
            ->write('\Craft::$app->end();');
    }
}
