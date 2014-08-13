<?php
namespace Craft;

/**
 * Class IncludeTranslations_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class IncludeTranslations_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% includeTranslations %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return IncludeTranslations_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$messages = array();

		while (true)
		{
			$messages[] = $this->parser->getExpressionParser()->parseExpression();

			if (!$stream->test(\Twig_Token::PUNCTUATION_TYPE, ','))
			{
				break;
			}

			$this->parser->getStream()->next();
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new IncludeTranslations_Node($messages, array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'includeTranslations';
	}
}
