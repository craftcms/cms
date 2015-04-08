<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class HeaderNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class HeaderNode extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->write('\craft\app\helpers\HeaderHelper::setHeader(')
			->subcompile($this->getNode('header'))
			->raw(");\n");
	}
}
