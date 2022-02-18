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
 * Class DNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write('dump(')
            ->subcompile($this->getNode('var'))
            ->raw(");\n");
    }
}
