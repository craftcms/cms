<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Twig\Nodes;

use CraftCms\Cms\Support\Html;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TagNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.6.0
 */
#[YieldReady]
class TagNode extends Node
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write('yield '.Html::class.'::tag(')
            ->subcompile($this->getNode('name'))
            ->raw(', ob_get_clean()');

        if ($this->hasNode('options')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('options'));
        }

        $compiler->raw(");\n");
    }
}
