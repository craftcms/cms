<?php
namespace Craft;

/**
 * Class RequireEdition_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.0
 */
class RequireEdition_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles a RequireEdition_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
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
