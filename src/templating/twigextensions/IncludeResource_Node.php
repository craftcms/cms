<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class IncludeResource_Node
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class IncludeResource_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles an IncludeResource_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$function = $this->getAttribute('function');
		$path = $this->getNode('path');

		$compiler
			->addDebugInfo($this)
			->write('\Craft::$app->templates->'.$function.'(')
			->subcompile($path);

		if ($this->getAttribute('first'))
		{
			$compiler->raw(', true');
		}

		$compiler->raw(");\n");
	}
}
