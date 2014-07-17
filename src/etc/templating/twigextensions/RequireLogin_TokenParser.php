<?php
namespace Craft;

/**
 * Class RequireLogin_TokenParser
 *
 * @package craft.app.etc.templating.twigextensions
 */
class RequireLogin_TokenParser extends \Twig_TokenParser
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
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequireLogin_Node(array(), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'requireLogin';
	}
}
