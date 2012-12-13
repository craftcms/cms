<?php
namespace Blocks;

/**
 *
 */
class RequirePermission_Node extends \Twig_Node
{
	/**
	 * Compiles a RequirePermission_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('\Blocks\blx()->user->requirePermission(')
		    ->subcompile($this->getNode('permissionName'))
		    ->raw(");\n");
	}
}
