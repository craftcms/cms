<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes;

use craft\helpers\Template;

/**
 * Internal node used by the nav node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NavItem_Node extends \Twig_Node
{
    // Public Methods
    // =========================================================================

    /**
     * NavItem_Node constructor.
     *
     * @param \Twig_Node_Expression_AssignName $valueTarget
     * @param \Twig_Node|null $indent
     * @param \Twig_Node|null $outdent
     * @param \Twig_Node|null $lowerBody
     * @param $lineno
     * @param $tag
     */
    public function __construct(\Twig_Node_Expression_AssignName $valueTarget, \Twig_Node $indent = null, \Twig_Node $outdent = null, \Twig_Node $lowerBody = null, $lineno, $tag = null)
    {
        parent::__construct([
            'value_target' => $valueTarget,
            'indent' => $indent,
            'outdent' => $outdent,
            'lower_body' => $lowerBody
        ], [], $lineno, $tag);
    }

    /**
     * @inheritdoc
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            // Get this item's level
            ->write('$_thisItemLevel = (int)' . Template::class . '::attribute($this->env, $this->getSourceContext(), ')
            ->subcompile($this->getNode('value_target'))
            ->raw(', \'level\', [], ' . \Twig_Template::class . "::ANY_CALL, false, true);\n")
            // Was there a previous item?
            ->write("if (isset(\$_contextsByLevel)) {\n")
            ->indent()
            // Temporarily set the context to the previous one
            ->write("\$_tmpContext = \$context;\n")
            ->write("\$context = \$_contextsByLevel[\$context['nav']['level']];\n")
            // Does this one have a greater level than the last one?
            ->write("if (\$_thisItemLevel > \$context['nav']['level']) {\n")
            ->indent()
            ->write("for (\$_i = \$context['nav']['level']; \$_i < \$_thisItemLevel; \$_i++) {\n")
            ->indent()
            ->subcompile($this->getNode('indent'), false)
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->subcompile($this->getNode('lower_body'), false)
            // Does this one have a lower level than the last one?
            ->write("if (\$_thisItemLevel < \$context['nav']['level']) {\n")
            ->indent()
            ->write("for (\$_i = \$context['nav']['level']-1; \$_i >= \$_thisItemLevel; \$_i--) {\n")
            ->indent()
            // Did we output an item at that level?
            ->write("if (isset(\$_contextsByLevel[\$_i])) {\n")
            ->indent()
            // Temporarily set the context to the element at this level
            ->write("\$context = \$_contextsByLevel[\$_i];\n")
            ->subcompile($this->getNode('outdent'), false)
            ->subcompile($this->getNode('lower_body'), false)
            ->write("unset(\$_contextsByLevel[\$_i]);\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n")
            // Set the context back
            ->write("\$context = \$_tmpContext;\n")
            ->write("unset(\$_tmpContext);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            // This is the first item so save its level
            ->write("\$_firstItemLevel = \$_thisItemLevel;\n")
            ->outdent()
            ->write("}\n")
            // Create the nav array for this item
            ->write("\$context['nav'] = ['level' => \$_thisItemLevel];\n")
            ->write("if (isset(\$_contextsByLevel[\$_thisItemLevel-1])) {\n")
            ->indent()
            ->write("\$context['nav']['parent'] = \$_contextsByLevel[\$_thisItemLevel-1];\n")
            // Might as well set the item's parent so long as we have it
            ->write('if (method_exists(')
            ->subcompile($this->getNode('value_target'))
            ->raw(", 'setParent')) {\n")
            ->indent()
            ->subcompile($this->getNode('value_target'), false)
            ->raw("->setParent(\$context['nav']['parent'][")
            ->string($this->getNode('value_target')->getAttribute('name'))
            ->raw("]);\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()
            ->write("\$context['nav']['parent'] = null;\n")
            ->outdent()
            ->write("}\n")
            // Save a reference of this item for the next iteration
            ->write("\$_contextsByLevel[\$_thisItemLevel] = \$context;\n");
    }
}
