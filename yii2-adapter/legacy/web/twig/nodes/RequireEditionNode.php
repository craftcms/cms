<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use CraftCms\Cms\Edition;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class RequireEditionNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
#[YieldReady]
class RequireEditionNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('if (' . Edition::class . '::get() < ')
            ->subcompile($this->getNode('editionName'))
            ->raw(")\n")
            ->write("{\n")
            ->indent()
            ->write("abort(404);\n")
            ->outdent()
            ->write("}\n");
    }
}
