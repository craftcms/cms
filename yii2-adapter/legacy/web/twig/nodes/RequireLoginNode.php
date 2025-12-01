<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Craft;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class RequireLoginNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RequireLoginNode extends Node
{
    /**
     * Compiles a RequireLoginNode into PHP.
     *
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class . "::\$app->controller->requireLogin();\n");
    }
}
