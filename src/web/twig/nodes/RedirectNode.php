<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
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
