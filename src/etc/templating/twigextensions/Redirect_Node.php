<?php
namespace Craft;

/**
 * Class Redirect_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Redirect_Node extends \Twig_Node
{
	/**
	 * Compiles a Redirect_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('\Craft\craft()->request->redirect(\Craft\UrlHelper::getUrl(')
		    ->subcompile($this->getNode('path'))
		    ->raw("));\n");
	}
}
