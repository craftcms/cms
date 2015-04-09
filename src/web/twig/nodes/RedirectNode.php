<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\nodes;

/**
 * Class RedirectNode
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RedirectNode extends \Twig_Node
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
		    ->write('\Craft::$app->getResponse()->redirect(\craft\app\helpers\UrlHelper::getUrl(')
		    ->subcompile($this->getNode('path'))
		    ->raw("), ")
		    ->subcompile($this->getNode('httpStatusCode'))
		    ->raw(");\n")
		    ->write('\Craft::$app->end();');
	}
}
