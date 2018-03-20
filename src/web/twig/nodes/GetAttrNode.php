<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;

/**
 * GetAttrNode is an alternative to Twig_Node_Expression_GetAttr, which sends attribute calls to [[Template::attribute()]] rather than twig_get_attribute().
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GetAttrNode extends \Twig_Node_Expression
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function compile(\Twig_Compiler $compiler)
    {
        // This is the only line that should be different from Twig_Node_Expression_GetAttr::compile()
        $compiler->raw(Template::class.'::attribute($this->env, $this->getSourceContext(), ');

        if ($this->getAttribute('ignore_strict_check')) {
            $this->getNode('node')->setAttribute('ignore_strict_check', true);
        }

        $compiler->subcompile($this->getNode('node'));

        $compiler->raw(', ')->subcompile($this->getNode('attribute'));

        // only generate optional arguments when needed (to make generated code more readable)
        $needFourth = $this->getAttribute('ignore_strict_check');
        $needThird = $needFourth || $this->getAttribute('is_defined_test');
        $needSecond = $needThird || \Twig_Template::ANY_CALL !== $this->getAttribute('type');
        $needFirst = $needSecond || $this->hasNode('arguments');

        if ($needFirst) {
            if ($this->hasNode('arguments')) {
                $compiler->raw(', ')->subcompile($this->getNode('arguments'));
            } else {
                $compiler->raw(', array()');
            }
        }

        if ($needSecond) {
            $compiler->raw(', ')->repr($this->getAttribute('type'));
        }

        if ($needThird) {
            $compiler->raw(', ')->repr($this->getAttribute('is_defined_test'));
        }

        if ($needFourth) {
            $compiler->raw(', ')->repr($this->getAttribute('ignore_strict_check'));
        }

        $compiler->raw(')');
    }
}
