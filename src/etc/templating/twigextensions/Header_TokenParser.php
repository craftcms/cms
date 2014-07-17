<?php
namespace Craft;

/**
 * Class Header_TokenParser
 *
 * @package craft.app.etc.templating.twigextensions
 */
class Header_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% requireLogin %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return RequireLogin_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$header = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Header_Node(array('header' => $header), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'header';
	}
}
