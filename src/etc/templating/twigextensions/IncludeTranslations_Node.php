<?php
namespace Craft;

/**
 * Class IncludeTranslations_Node
 *
 * @package craft.app.etc.templating.twigextensions
 */
class IncludeTranslations_Node extends \Twig_Node
{
	/**
	 * Compiles an IncludeTranslations_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write("\Craft\craft()->templates->includeTranslations(\n");

		foreach ($this->nodes as $index => $node)
		{
			if ($index > 0)
			{
				$compiler->raw(",\n");
			}

			$compiler->write("\t")->subcompile($node);
		}

		$compiler->raw("\n")->write(");\n");
	}
}
