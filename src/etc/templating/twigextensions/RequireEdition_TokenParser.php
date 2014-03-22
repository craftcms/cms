<?php
namespace Craft;

/**
 *
 */
class RequireEdition_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% requireEdition %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return RequireEdition_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$editionName = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequireEdition_Node(array('editionName' => $editionName), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'requireEdition';
	}
}
