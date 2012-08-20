<?php
namespace Blocks;

/**
 *
 */
class IncludeTranslation_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeTranslation_Node into PHP.
	 */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        foreach ($this->nodes as $node)
        {
        	$compiler
	            ->write('$context[\'translations\'][] = ')
	            ->subcompile($node)
	            ->raw(";\n");
        }
    }
}
