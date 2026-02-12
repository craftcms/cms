<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\debug\DumpPanel;
use craft\helpers\Template;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class DumpNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class DumpNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('%s::dump(', DumpPanel::class));

        if ($this->hasNode('var')) {
            $compiler->subcompile($this->getNode('var'));
        } else {
            $compiler->raw(sprintf('%s::contextWithoutTemplate($context)', Template::class));
        }

        $compiler
            ->raw(sprintf(", '%s', %s);\n", $this->getTemplateName(), $this->getTemplateLine()));
    }
}
