<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class IncludeTranslationsNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class IncludeTranslationsNode extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write("\\Craft::\$app->getView()->includeTranslations(\n");

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
