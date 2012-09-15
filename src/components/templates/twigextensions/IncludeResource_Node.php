<?php
namespace Blocks;

/**
 *
 */
class IncludeResource_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeResource_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$path = $this->getNode('path');
		$function = $this->getAttribute('function');

		$compiler
			->addDebugInfo($this)
			->write('\Blocks\blx()->templates->'.$function.'(')
			->subcompile($path)
		    ->raw(");\n");
	}
}
