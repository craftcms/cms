<?php
namespace Blocks;

/**
 *
 */
class IncludeResource_TokenParser extends \Twig_TokenParser
{
	private $_tag;

	/**
	 * Constructor
	 *
	 * @param string $tag
	 */
	function __construct($tag)
	{
		$this->_tag = $tag;
	}

	/**
	 * Parses resource include tags.
	 *
	 * @param \Twig_Token $token
	 * @return IncludeResource_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$path = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new IncludeResource_Node(array('path' => $path), array('function' => $this->_tag), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return $this->_tag;
	}
}
