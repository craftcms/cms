<?php
namespace Craft;

/**
 * Class Header_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.3
 */
class Header_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles a Header_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->write('\Craft\HeaderHelper::setHeader(')
			->subcompile($this->getNode('header'))
			->raw(");\n");
	}
}
