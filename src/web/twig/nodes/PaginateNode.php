<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

/**
 * Represents a paginate node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PaginateNode extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            // the (array) cast bypasses a PHP 5.2.6 bug
            //->write("\$context['_parent'] = (array) \$context;\n")
            ->write('list(')
            ->subcompile($this->getNode('paginateTarget'))
            ->raw(', ')
            ->subcompile($this->getNode('elementsTarget'))
            ->raw(') = \craft\helpers\Template::paginateCriteria(')
            ->subcompile($this->getNode('criteria'))
            ->raw(");\n");
    }
}
