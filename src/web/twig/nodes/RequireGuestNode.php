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
 * Class RequireGuestNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class RequireGuestNode extends Node
{
    /**
     * Compiles a RequireGuestNode into PHP.
     *
     * @param Compiler $compiler
     */
    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(Craft::class . "::\$app->controller->requireGuest();\n");
    }
}
