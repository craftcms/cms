<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\NamespaceNode;

/**
 * Class NamespaceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NamespaceTokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'namespace';
	}

	/**
	 * @inheritdoc
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$namespace = $this->parser->getExpressionParser()->parseExpression();
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideNamespaceEnd'], true);
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new NamespaceNode(['namespace' => $namespace, 'body' => $body], [], $lineno, $this->getTag());
	}


	/**
	 * @param \Twig_Token $token
	 *
	 * @return bool
	 */
	public function decideNamespaceEnd(\Twig_Token $token)
	{
		return $token->test('endnamespace');
	}
}
