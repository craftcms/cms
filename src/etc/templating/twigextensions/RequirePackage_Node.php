<?php
namespace Blocks;

/**
 *
 */
class RequirePackage_Node extends \Twig_Node
{
	/**
	 * Compiles a RequirePackage_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('\Blocks\Blocks::requirePackage(')
		    ->subcompile($this->getNode('packageName'))
		    ->raw(");\n");
	}
}
