<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Switch_Node
 *
 * Based on the rejected Twig pull request: https://github.com/fabpot/Twig/pull/185
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */

class Switch_Node extends \Twig_Node
{
	// Properties
	// =========================================================================

	/**
	 * @var \Twig_NodeInterface
	 */
	private $_cases;

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
		$this->_cases = $cases;

		parent::__construct(['value' => $value, 'default' => $default], [], $lineno, $tag);
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

		foreach ($this->_cases as $case)
		{
			$compiler
				->write('case ')
				->subcompile($case['expr'])
				->raw(":\n")
				->write("{\n")
				->indent()
				->subcompile($case['body'])
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
