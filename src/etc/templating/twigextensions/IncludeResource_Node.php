<?php
namespace Craft;

/**
 * Class IncludeResource_Node
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
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
		$value = $this->getNode('value');

		$compiler
			->addDebugInfo($this);

		if ($this->getAttribute('capture'))
		{
			$compiler
				->write("ob_start();\n")
				->subcompile($value)
				->write("\$_js = ob_get_clean();\n")
			;
		}
		else
		{
			$compiler
				->write("\$_js = ")
				->subcompile($value)
				->raw(";\n")
			;
		}

		$compiler
			->write("\\Craft\\craft()->templates->{$function}(\$_js")
		;

		if ($this->getAttribute('first'))
		{
			$compiler->raw(', true');
		}

		$compiler->raw(");\n");
	}
}
