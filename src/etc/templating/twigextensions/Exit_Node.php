<?php
namespace Craft;

/**
 * Class Exit_Node
 *
 * @package craft.app.etc.templating.twigextensions
 */
class Exit_Node extends \Twig_Node
{
	/**
	 * Compiles a Exit_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		if ($status = $this->getNode('status'))
		{
			$compiler
				->write('throw new \Craft\HttpException(')
				->subcompile($status)
				->raw(");\n");
		}
		else
		{
			$compiler->write("\Craft\craft()->end();\n");
		}
	}
}
