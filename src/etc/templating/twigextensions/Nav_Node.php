<?php
namespace Craft;

/**
 * Represents a nav node.
 */
class Nav_Node extends \Twig_Node_For
{
	protected $navItemNode;

	public function __construct(\Twig_Node_Expression_AssignName $keyTarget, \Twig_Node_Expression_AssignName $valueTarget, \Twig_Node_Expression $seq, \Twig_NodeInterface $upperBody, \Twig_NodeInterface $lowerBody = null, \Twig_NodeInterface $indent = null, \Twig_NodeInterface $outdent = null, $lineno, $tag = null)
	{
		$this->navItemNode = new NavItem_Node($valueTarget, $indent, $outdent, $lowerBody, $lineno, $tag);
		$body = new \Twig_Node(array($this->navItemNode, $upperBody));

		parent::__construct($keyTarget, $valueTarget, $seq, null, $body, null, $lineno, $tag);
	}

	/**
	 * Compiles the node to PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		parent::compile($compiler);

		$compiler
			// Were there any items?
			->write("if (isset(\$_thisItemDepth)) {\n")
			->indent()
				// Remember the current context
				->write("\$_tmpContext = \$context;\n")
				// Close out the unclosed items
				->write("if (\$_thisItemDepth > \$_firstItemDepth) {\n")
				->indent()
					->write("for (\$_i = \$_thisItemDepth; \$_i > \$_firstItemDepth; \$_i--) {\n")
					->indent()
						// Did we output an item at that depth?
						->write("if (isset(\$_contextsByDepth[\$_i])) {\n")
						->indent()
							// Temporarily set the context to the element at this depth
							->write("\$context = \$_contextsByDepth[\$_i];\n")
							->subcompile($this->navItemNode->getNode('lower_body'))
							->subcompile($this->navItemNode->getNode('outdent'))
						->outdent()
						->write("}\n")
					->outdent()
					->write("}\n")
				->outdent()
				->write("}\n")
				// Close out the last item
				->write("\$context = \$_contextsByDepth[\$_firstItemDepth];\n")
				->subcompile($this->navItemNode->getNode('lower_body'))
				// Set the context back
				->write("\$context = \$_tmpContext;\n")
				// Unset out variables
				->write("unset(\$_thisItemDepth, \$_firstItemDepth, \$_i, \$_contextsByDepth, \$_tmpContext);\n")
			->outdent()
			->write("}\n")
		;
	}
}
