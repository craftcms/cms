<?php
namespace Blocks;

/**
 *
 */
class IncludeJs_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeJs_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		foreach ($this->nodes as $node)
		{
			$compiler
			    ->write('$context[\'jsIncludes\'][] = ')
			    ->subcompile($node)
			    ->raw(";\n");
		}
	}
}
