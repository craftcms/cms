<?php
namespace Blocks;

/**
 *
 */
class IncludeCss_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% include_css %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return IncludeCss_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$sources = array();

		while (true)
		{
			$sources[] = $this->parser->getExpressionParser()->parseExpression();

			if (!$stream->test(\Twig_Token::PUNCTUATION_TYPE, ','))
				break;

			$this->parser->getStream()->next();
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new IncludeCss_Node($sources, array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'include_css';
	}
}
