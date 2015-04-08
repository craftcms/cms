<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class RequireEditionNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireEditionNode extends \Twig_Node
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
			->write('if (\Craft::$app->getEdition() < ')
			->subcompile($this->getNode('editionName'))
			->raw(")\n")
			->write("{\n")
			->indent()
			->write("throw new \\Craft\\app\\errors\\HttpException(404);\n")
			->outdent()
			->write("}\n");
	}
}
