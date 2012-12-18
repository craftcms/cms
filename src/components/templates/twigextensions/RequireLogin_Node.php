<?php
namespace Blocks;

/**
 *
 */
class RequireLogin_Node extends \Twig_Node
{
	/**
	 * Compiles a RequireLogin_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write("\Blocks\blx()->user->requireLogin();\n");
	}
}
