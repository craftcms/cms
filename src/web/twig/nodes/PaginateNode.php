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
 * Represents a paginate node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PaginateNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            // the (array) cast bypasses a PHP 5.2.6 bug
            //->write("\$context['_parent'] = (array) \$context;\n")
            ->write('[')
            ->subcompile($this->getNode('infoVariable'))
            ->raw(', ')
            ->subcompile($this->getNode('resultVariable'))
            ->raw('] = \craft\helpers\Template::paginateQuery(')
            ->subcompile($this->getNode('query'))
            ->raw(");\n");
    }
}
