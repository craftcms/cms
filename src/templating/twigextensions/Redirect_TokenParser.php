<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Redirect_TokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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

		return new Redirect_Node(['path' => $path, 'httpStatusCode' => $httpStatusCode], [], $lineno, $this->getTag());
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
