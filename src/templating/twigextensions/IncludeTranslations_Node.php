<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class IncludeTranslations_Node
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class IncludeTranslations_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles an IncludeTranslations_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write("\\Craft::\$app->templates->includeTranslations(\n");

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
