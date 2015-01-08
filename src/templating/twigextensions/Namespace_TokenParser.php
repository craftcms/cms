<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\templating\twigextensions;

/**
 * Class Namespace_TokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Namespace_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Defines the tag name.
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'namespace';
	}

	/**
	 * Parses {% namespace %}...{% endnamespace %} tags.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return Namespace_Node
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$namespace = $this->parser->getExpressionParser()->parseExpression();
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideNamespaceEnd'], true);
		$stream->expect(\Twig_Token::BLOCK_END_TYPE);

		return new Namespace_Node(['namespace' => $namespace, 'body' => $body], [], $lineno, $this->getTag());
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
