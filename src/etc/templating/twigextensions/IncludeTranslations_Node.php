<?php
namespace Craft;

/**
 * Class IncludeTranslations_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
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
