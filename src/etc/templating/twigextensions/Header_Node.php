<?php
namespace Craft;

/**
 *
 */
class Header_Node extends \Twig_Node
{
	/**
	 * Compiles a Header_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->write('\Craft\HeaderHelper::setHeader(')
			->subcompile($this->getNode('header'))
			->raw(");\n");
	}
}
