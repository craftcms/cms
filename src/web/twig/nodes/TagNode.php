<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Html;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TagNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class TagNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write('echo ' . Html::class . '::tag(')
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
