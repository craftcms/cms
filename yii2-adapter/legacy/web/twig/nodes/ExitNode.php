<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class ExitNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
#[YieldReady]
class ExitNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if ($this->hasNode('status')) {
            $status = $this->getNode('status')->getAttribute('value');

            $compiler
                ->write(sprintf('abort(%d', $status));

            if ($this->hasNode('message')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($this->getNode('message'));
            }

            $compiler->raw(");\n");
        } else {
            $compiler->write("abort(0);\n");
        }
    }
}
