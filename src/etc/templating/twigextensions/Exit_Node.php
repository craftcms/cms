<?php
namespace Craft;

/**
 * Class Exit_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Exit_Node extends \Twig_Node
{
	/**
	 * Compiles a Exit_Node into PHP.
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler->addDebugInfo($this);

		if ($status = $this->getNode('status'))
		{
			$compiler
				->write('throw new \Craft\HttpException(')
				->subcompile($status)
				->raw(");\n");
		}
		else
		{
			$compiler->write("\Craft\craft()->end();\n");
		}
	}
}
