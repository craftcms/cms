<?php
namespace Blocks;

/**
 *
 */
class IncludeCss_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeCss_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		foreach ($this->nodes as $node)
		{
			$compiler
			    ->write('$context[\'cssIncludes\'][] = ')
			    ->subcompile($node)
			    ->raw(";\n");
		}
	}
}
