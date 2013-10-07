<?php
namespace Craft;

/**
 *
 */
class Redirect_Node extends \Twig_Node
{
	/**
	 * Compiles a Redirect_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('\Craft\HeaderHelper::setHeader(array(\'Location\' => ')
		    ->subcompile($this->getNode('path'))
		    ->raw(").'');\n");
	}
}
