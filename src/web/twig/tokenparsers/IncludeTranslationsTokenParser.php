<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\IncludeTranslationsNode;

/**
 * Class IncludeTranslationsTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class IncludeTranslationsTokenParser extends \Twig_TokenParser
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
		$messages = [];

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

		return new IncludeTranslationsNode($messages, [], $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'includeTranslations';
	}
}
