<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class RequireLoginNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RequireLoginNode extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * Compiles a RequireLoginNode into PHP.
     *
     * @param \Twig_Compiler $compiler
     *
     * @return void
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("\\Craft::\$app->controller->requireLogin();\n");
    }
}
