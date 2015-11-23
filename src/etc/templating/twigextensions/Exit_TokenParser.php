<?php
namespace Craft;

/**
 * Class Exit_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Exit_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% exit %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Exit_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		if ($stream->test(\Twig_Token::NUMBER_TYPE))
		{
			$status = $this->parser->getExpressionParser()->parseExpression();
		}
		else
		{
			$status = null;
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Exit_Node(array('status' => $status), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'exit';
	}
}
