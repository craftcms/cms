<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;

/**
 * Class RequireLoginNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireLoginNode extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * Compiles a RequireLoginNode into PHP.
     *
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class . "::\$app->controller->requireLogin();\n");
    }
}
