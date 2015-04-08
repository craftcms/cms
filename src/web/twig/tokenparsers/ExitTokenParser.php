<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\ExitNode;

/**
 * Class ExitTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ExitTokenParser extends \Twig_TokenParser
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

		if ($stream->test(\Twig_Token::NUMBER_TYPE))
		{
			$status = $this->parser->getExpressionParser()->parseExpression();
		}
		else
		{
			$status = null;
		}

		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new ExitNode(['status' => $status], [], $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'exit';
	}
}
