<?php
namespace Craft;

/**
 * Represents a paginate node.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Paginate_Node extends \Twig_Node
{
	/**
	 * Compiles the node to PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return void
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			// the (array) cast bypasses a PHP 5.2.6 bug
			//->write("\$context['_parent'] = (array) \$context;\n")
			->write("list(\$context['paginate'], ")
			->subcompile($this->getNode('entitiesTarget'))
			->raw(') = \Craft\TemplateHelper::paginateCriteria(')
			->subcompile($this->getNode('criteria'))
			->raw(");\n")
			->subcompile($this->getNode('body'), false)
			->write('unset($context[\'paginate\'], ')
			->subcompile($this->getNode('entitiesTarget'))
			->raw(");\n");
	}
}
