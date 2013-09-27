<?php
namespace Craft;

/**
 *
 */
class RequirePackage_Node extends \Twig_Node
{
	/**
	 * Compiles a RequirePackage_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write('if (!\Craft\craft()->hasPackage(')
			->subcompile($this->getNode('packageName'))
			->raw("))\n")
			->write("{\n")
			->write("\tthrow new \Craft\HttpException(404);\n")
			->write("}\n");
	}
}
