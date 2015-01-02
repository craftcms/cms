<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Hook_Node
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Hook_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * Compiles a Hook_Node into PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
		    ->addDebugInfo($this)
		    ->write('echo \Craft\craft()->templates->invokeHook(')
		    ->subcompile($this->getNode('hook'))
		    ->raw(", \$context);\n\n");
	}
}
