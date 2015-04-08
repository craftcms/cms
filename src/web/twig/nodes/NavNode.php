<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Represents a nav node.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NavNode extends \Twig_Node_For
{
	// Properties
	// =========================================================================

	/**
	 * @var NavItem_Node
	 */
	protected $navItemNode;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function __construct(\Twig_Node_Expression_AssignName $keyTarget, \Twig_Node_Expression_AssignName $valueTarget, \Twig_Node_Expression $seq, \Twig_NodeInterface $upperBody, \Twig_NodeInterface $lowerBody = null, \Twig_NodeInterface $indent = null, \Twig_NodeInterface $outdent = null, $lineno, $tag = null)
	{
		$this->navItemNode = new NavItem_Node($valueTarget, $indent, $outdent, $lowerBody, $lineno, $tag);
		$body = new \Twig_Node([$this->navItemNode, $upperBody]);

		parent::__construct($keyTarget, $valueTarget, $seq, null, $body, null, $lineno, $tag);
	}

	/**
	 * @inheritdoc
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		// Remember what 'nav' was set to before
		$compiler
			->write("if (isset(\$context['nav'])) {\n")
			->indent()
				->write("\$_nav = \$context['nav'];\n")
			->outdent()
			->write("}\n");

		parent::compile($compiler);

		$compiler
			// Were there any items?
			->write("if (isset(\$_thisItemLevel)) {\n")
			->indent()
				// Remember the current context
				->write("\$_tmpContext = \$context;\n")
				// Close out the unclosed items
				->write("if (\$_thisItemLevel > \$_firstItemLevel) {\n")
				->indent()
					->write("for (\$_i = \$_thisItemLevel; \$_i > \$_firstItemLevel; \$_i--) {\n")
					->indent()
						// Did we output an item at that level?
						->write("if (isset(\$_contextsByLevel[\$_i])) {\n")
						->indent()
							// Temporarily set the context to the element at this level
							->write("\$context = \$_contextsByLevel[\$_i];\n")
							->subcompile($this->navItemNode->getNode('lower_body'), false)
							->subcompile($this->navItemNode->getNode('outdent'), false)
						->outdent()
						->write("}\n")
					->outdent()
					->write("}\n")
				->outdent()
				->write("}\n")
				// Close out the last item
				->write("\$context = \$_contextsByLevel[\$_firstItemLevel];\n")
				->subcompile($this->navItemNode->getNode('lower_body'), false)
				// Set the context back
				->write("\$context = \$_tmpContext;\n")
				// Unset out variables
				->write("unset(\$_thisItemLevel, \$_firstItemLevel, \$_i, \$_contextsByLevel, \$_tmpContext);\n")
			->outdent()
			->write("}\n")
			// Bring back the 'nav' variable
			->write("if (isset(\$_nav)) {\n")
			->indent()
				->write("\$context['nav'] = \$_nav;\n")
				->write("unset(\$_nav);\n")
			->outdent()
			->write("}\n")
		;
	}
}
