<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\RedirectNode;

/**
 * Class RedirectTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RedirectTokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
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

		return new RedirectNode(['path' => $path, 'httpStatusCode' => $httpStatusCode], [], $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'redirect';
	}
}
