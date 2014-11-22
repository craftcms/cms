<?php
namespace Craft;

/**
 * Class Redirect_TokenParser
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Redirect_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses {% redirect %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Redirect_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		$path = $this->parser->getExpressionParser()->parseExpression();

		if ($stream->test(\Twig_Token::NUMBER_TYPE))
		{
			$httpStatusCode = $this->parser->getExpressionParser()->parseExpression();
		}
		else
		{
			$httpStatusCode = new \Twig_Node_Expression_Constant(302, 1);
		}

		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Redirect_Node(array('path' => $path, 'httpStatusCode' => $httpStatusCode), array(), $lineno, $this->getTag());
	}

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'redirect';
	}
}
