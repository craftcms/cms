<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class HookNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class HookNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('echo \Craft::$app->getView()->invokeHook(')
            ->subcompile($this->getNode('hook'))
            ->raw(", \$context);\n\n");
    }
}
