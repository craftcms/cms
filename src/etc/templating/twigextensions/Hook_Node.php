<?php
namespace Craft;

/**
 * Class Hook_Node
 *
 * @package craft.app.etc.templating.twigextensions
 */
class Hook_Node extends \Twig_Node
{
	/**
	 * Compiles a Hook_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('echo \Craft\craft()->templates->invokeHook(')
		    ->subcompile($this->getNode('hook'))
		    ->raw(", \$context);\n\n");
	}
}
