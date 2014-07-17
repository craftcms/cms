<?php
namespace Craft;

/**
 * Class RequireEdition_Node
 *
 * @package craft.app.etc.templating.twigextensions
 */
class RequireEdition_Node extends \Twig_Node
{
	/**
	 * Compiles a RequireEdition_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write('if (\Craft\craft()->getEdition() < ')
			->subcompile($this->getNode('editionName'))
			->raw(")\n")
			->write("{\n")
			->indent()
			->write("throw new \Craft\HttpException(404);\n")
			->outdent()
			->write("}\n");
	}
}
