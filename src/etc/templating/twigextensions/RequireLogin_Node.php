<?php
namespace Craft;

/**
 * Class RequireLogin_Node
 *
 * @package craft.app.etc.templating.twigextensions
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
		    ->write("\Craft\craft()->userSession->requireLogin();\n");
	}
}
