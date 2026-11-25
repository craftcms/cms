<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Html;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TagNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
#[YieldReady]
class TagNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $content = new CaptureNode($this->getNode('content'), $this->getTemplateLine());
        $var = $compiler->getVarName();

        $compiler
            ->addDebugInfo($this)
            ->write("\$$var = ")
            ->subcompile($content)
            ->raw("\n")
            ->write('yield ' . Html::class . '::tag(')
            ->subcompile($this->getNode('name'))
            ->raw(", \$$var");

        if ($this->hasNode('options')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('options'));
        }

        $compiler
            ->raw(");\n")
            ->write("unset(\$$var);\n");
    }
}
