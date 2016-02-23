<?php
namespace Craft;

/**
 * Class Switch_Node
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.3
 */

class Switch_Node extends \Twig_Node
{
	// Public Methods
	// =========================================================================

	/**
	 * @param \Twig_NodeInterface $value
	 * @param \Twig_NodeInterface $cases
	 * @param \Twig_NodeInterface $default
	 * @param null|string         $lineno
	 * @param null                $tag
	 *
	 * @return Switch_Node
	 */
	public function __construct(\Twig_NodeInterface $value, \Twig_NodeInterface $cases, \Twig_NodeInterface $default = null, $lineno, $tag = null)
	{
		parent::__construct(array('value' => $value, 'cases' => $cases, 'default' => $default), array(), $lineno, $tag);
	}

	/**
	 * Compiles the node to PHP.
	 *
	 * @param \Twig_Compiler $compiler
	 *
	 * @return null
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->addDebugInfo($this)
			->write("switch (")
			->subcompile($this->getNode('value'))
			->raw(") {\n")
			->indent();

		foreach ($this->getNode('cases') as $case)
		{
			// The 'body' node may have been removed by Twig if it was an empty text node in a sub-template,
			// outside of any blocks
			if (!$case->hasNode('body'))
			{
				continue;
			}

			foreach ($case->getNode('values') as $value)
			{
				$compiler
					->write('case ')
					->subcompile($value)
					->raw(":\n");
			}

			$compiler
				->write("{\n")
				->indent()
				->subcompile($case->getNode('body'))
				->write("break;\n")
				->outdent()
				->write("}\n");
		}

		if ($this->hasNode('default') && $this->getNode('default') !== null)
		{
			$compiler
				->write("default:\n")
				->write("{\n")
				->indent()
				->subcompile($this->getNode('default'))
				->outdent()
				->write("}\n");
		}

		$compiler
			->outdent()
			->write("}\n");
	}
}
