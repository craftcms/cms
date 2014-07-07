<?php
namespace Craft;

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
		    ->write("if (\Craft\craft()->config->get(\"loginPath\") !== \Craft\craft()->request->getPath())\n")
			->write("{\n")
			->write("\t\Craft\craft()->userSession->requireLogin();\n")
		    ->write("}\n")
		    ->write("else\n")
		    ->write("{\n")
		    ->write("\tthrow new \Craft\Exception(\"The {% requireLogin %} tag was used on the login page, creating an infinite loop.\");\n")
		    ->write("}\n");
	}
}
